(() => {
  const root = window.TitanWorkCore = window.TitanWorkCore || {};
  const DB = () => root.db;
  const S = () => root.contracts.stores;
  const DEFAULTS = Object.freeze({
    maxBytes: 25 * 1024 * 1024,
    imageMaxBytes: 12 * 1024 * 1024,
    videoMaxBytes: 75 * 1024 * 1024,
    chunkBytes: 1024 * 1024,
    imageMaxDimension: 2048,
    imageQuality: 0.82,
    allowed: [
      'image/jpeg','image/png','image/webp','image/gif','application/pdf','text/plain','text/csv',
      'application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      'application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'audio/mpeg','audio/mp4','audio/webm','audio/wav','video/mp4','video/webm'
    ]
  });
  const EXTENSIONS = Object.freeze({
    'image/jpeg':['jpg','jpeg'],'image/png':['png'],'image/webp':['webp'],'image/gif':['gif'],
    'application/pdf':['pdf'],'text/plain':['txt'],'text/csv':['csv'],'application/msword':['doc'],
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document':['docx'],
    'application/vnd.ms-excel':['xls'],'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':['xlsx'],
    'audio/mpeg':['mp3'],'audio/mp4':['m4a','mp4'],'audio/webm':['webm'],'audio/wav':['wav'],
    'video/mp4':['mp4','m4v'],'video/webm':['webm']
  });
  const emit = (name, detail = {}) => window.dispatchEvent(new CustomEvent(name, { detail }));
  const now = () => new Date().toISOString();


  const activeUploads = new Map();
  async function sniffMime(blob) {
    const bytes = new Uint8Array(await blob.slice(0, 16).arrayBuffer());
    const hex = [...bytes].map(v => v.toString(16).padStart(2, '0')).join('');
    if (hex.startsWith('ffd8ff')) return 'image/jpeg';
    if (hex.startsWith('89504e470d0a1a0a')) return 'image/png';
    if (hex.startsWith('47494638')) return 'image/gif';
    if (hex.startsWith('25504446')) return 'application/pdf';
    if (hex.startsWith('52494646') && String.fromCharCode(...bytes.slice(8,12)) === 'WEBP') return 'image/webp';
    if (hex.startsWith('504b0304')) return 'application/zip';
    return null;
  }
  async function verifySignature(blob, declaredType) {
    const detected = await sniffMime(blob);
    const strict = ['image/jpeg','image/png','image/gif','image/webp','application/pdf'];
    if (strict.includes(declaredType) && !detected) throw new Error(`Unable to verify file signature for ${declaredType}.`);
    if (strict.includes(declaredType) && detected !== declaredType) throw new Error(`File signature does not match declared MIME type (${declaredType}).`);
    return detected;
  }
  async function reconcileSession(record, signal) {
    if (!record.upload_session_id) return record;
    const response = await fetch(root.contracts.endpoints.attachmentSession(record.upload_session_id), { credentials:'same-origin', signal, headers:{Accept:'application/json'} });
    if ([404, 405, 410].includes(response.status)) { record.upload_session_id=null; record.uploaded_bytes=0; return record; }
    if (!response.ok) throw new Error(`Upload session status failed (${response.status})`);
    const status = await response.json();
    record.uploaded_bytes = Math.max(0, Math.min(record.size, Number(status.uploaded_bytes || 0)));
    record.chunk_size = Number(status.chunk_size || record.chunk_size);
    return record;
  }
  function cancelUpload(id) { const controller=activeUploads.get(id); if(!controller)return false; controller.abort(); return true; }

  async function sha256(blob) {
    const hash = await crypto.subtle.digest('SHA-256', await blob.arrayBuffer());
    return [...new Uint8Array(hash)].map(v => v.toString(16).padStart(2, '0')).join('');
  }
  async function opfsRoot() { return navigator.storage?.getDirectory ? navigator.storage.getDirectory() : null; }
  async function getDirectory(name, create = false) {
    const rootDir = await opfsRoot(); if (!rootDir) return null;
    return rootDir.getDirectoryHandle(name, { create });
  }
  async function writeFile(id, blob, directory = 'titan-workcore') {
    const dir = await getDirectory(directory, true);
    if (!dir) return { storage: 'indexeddb', blob };
    const file = await dir.getFileHandle(id, { create: true });
    const stream = await file.createWritable(); await stream.write(blob); await stream.close();
    return { storage: 'opfs', directory, path: `${directory}/${id}` };
  }
  async function readFile(record) {
    if (record.storage === 'indexeddb') return record.blob;
    const dir = await getDirectory(record.directory || 'titan-workcore');
    if (!dir) throw new Error('OPFS is unavailable');
    return (await (await dir.getFileHandle(record.id)).getFile());
  }
  async function removeFile(record) {
    if (record.storage !== 'opfs') return;
    try { const dir = await getDirectory(record.directory || 'titan-workcore'); await dir?.removeEntry(record.id); } catch (_) {}
  }
  function extension(name = '') { const match = String(name).toLowerCase().match(/\.([a-z0-9]+)$/); return match?.[1] || ''; }
  function limits(options = {}) { return { ...DEFAULTS, ...(root.config?.attachments || {}), ...options }; }
  function validate(blob, metadata = {}, options = {}) {
    if (!(blob instanceof Blob)) throw new TypeError('Attachment must be a Blob or File.');
    const cfg = limits(options), type = blob.type || metadata.type || 'application/octet-stream';
    const name = metadata.name || blob.name || 'attachment';
    if (!cfg.allowed.includes(type)) throw new Error(`Unsupported attachment type: ${type}`);
    const max = type.startsWith('video/') ? cfg.videoMaxBytes : type.startsWith('image/') ? cfg.imageMaxBytes : cfg.maxBytes;
    if (blob.size > max) throw new Error(`Attachment exceeds the configured ${Math.round(max / 1048576)} MB limit.`);
    const ext = extension(name), expected = EXTENSIONS[type];
    if (expected && ext && !expected.includes(ext)) throw new Error('The file extension does not match its MIME type.');
    return { type, name, ext, maxBytes: max };
  }
  async function imageFromBlob(blob) {
    if ('createImageBitmap' in window) return createImageBitmap(blob, { imageOrientation: 'from-image' });
    return new Promise((resolve, reject) => { const img = new Image(); const url = URL.createObjectURL(blob); img.onload=()=>{URL.revokeObjectURL(url);resolve(img)}; img.onerror=()=>{URL.revokeObjectURL(url);reject(new Error('Image decode failed'))}; img.src=url; });
  }
  async function canvasBlob(canvas, type, quality) {
    return new Promise((resolve, reject) => canvas.toBlob(value => value ? resolve(value) : reject(new Error('Image encoding failed')), type, quality));
  }
  async function processImage(blob, options = {}) {
    if (!blob.type.startsWith('image/') || blob.type === 'image/gif') return { blob, thumbnail: null };
    const cfg = limits(options), image = await imageFromBlob(blob);
    const width = image.width || image.naturalWidth, height = image.height || image.naturalHeight;
    const scale = Math.min(1, cfg.imageMaxDimension / Math.max(width, height));
    const canvas = document.createElement('canvas'); canvas.width = Math.max(1, Math.round(width * scale)); canvas.height = Math.max(1, Math.round(height * scale));
    canvas.getContext('2d').drawImage(image, 0, 0, canvas.width, canvas.height); image.close?.();
    const outputType = blob.type === 'image/png' ? 'image/png' : 'image/jpeg';
    const compressed = await canvasBlob(canvas, outputType, cfg.imageQuality);
    const thumbScale = Math.min(1, 320 / Math.max(canvas.width, canvas.height));
    const thumb = document.createElement('canvas'); thumb.width=Math.max(1,Math.round(canvas.width*thumbScale)); thumb.height=Math.max(1,Math.round(canvas.height*thumbScale));
    thumb.getContext('2d').drawImage(canvas, 0, 0, thumb.width, thumb.height);
    return { blob: compressed.size < blob.size ? compressed : blob, thumbnail: await canvasBlob(thumb, 'image/jpeg', 0.72), width: canvas.width, height: canvas.height };
  }
  async function findDuplicate(checksum) { return (await DB().byIndex(S().attachments, 'by_checksum', checksum))[0] || null; }
  async function save(blob, metadata = {}, options = {}) {
    const valid = validate(blob, metadata, options); await verifySignature(blob, valid.type); const processed = await processImage(blob, options);
    const checksum = await sha256(processed.blob), duplicate = await findDuplicate(checksum);
    if (duplicate && metadata.allow_duplicate !== true) return { ...duplicate, duplicate_of: duplicate.id };
    const id = metadata.id || crypto.randomUUID(), stored = await writeFile(id, processed.blob);
    let thumbnail = null;
    if (processed.thumbnail) { const thumbId = `${id}.thumb`; thumbnail = { id: thumbId, ...(await writeFile(thumbId, processed.thumbnail, 'titan-workcore-thumbnails')) }; }
    const row = {
      id, local_uuid: metadata.local_uuid || id, name: valid.name, type: processed.blob.type || valid.type,
      original_type: valid.type, size: processed.blob.size, original_size: blob.size, checksum, state: 'local',
      operation_id: metadata.operation_id || null, resource_key: metadata.resource_key || null, pack_id: metadata.pack_id || null, pack_ids: metadata.pack_id ? [String(metadata.pack_id)] : [],
      tenant_id: metadata.tenant_id || root.context?.company_id || null, user_id: metadata.user_id || root.context?.user_id || null,
      device_id: metadata.device_id || root.context?.device_id || null, upload_session_id: null, uploaded_bytes: 0,
      chunk_size: limits(options).chunkBytes, thumbnail, width: processed.width || null, height: processed.height || null,
      created_at: now(), updated_at: now(), synced_at: null, protected_until_synced: true, ...stored
    };
    await DB().put(S().attachments, row); emit('workcore:attachment-saved', { attachment: row }); return row;
  }
  async function get(id) { const row = await DB().get(S().attachments, id); return row ? { record: row, blob: await readFile(row) } : null; }
  async function removeAttachmentChunks(id) { const chunks=await DB().byIndex(S().attachmentChunks,'by_attachment',id); for(const chunk of chunks)await DB().remove(S().attachmentChunks,chunk.key); return chunks.length; }
  async function remove(id, options = {}) {
    const row = await DB().get(S().attachments, id); if (!row) return false;
    if (row.protected_until_synced && row.state !== 'synced' && !options.force) throw new Error('Unsynchronised attachments cannot be deleted.');
    await removeAttachmentChunks(id);
    await removeFile(row);
    if (row.thumbnail) await removeFile({ ...row.thumbnail, directory: 'titan-workcore-thumbnails' });
    await DB().remove(S().attachments, id); emit('workcore:attachment-removed', { id }); return true;
  }
  async function createSession(record, signal) {
    const response = await fetch(root.contracts.endpoints.attachmentSessions, { method:'POST', credentials:'same-origin', signal, headers:{'Content-Type':'application/json','Accept':'application/json'}, body:JSON.stringify({
      attachment_id: record.id, name: record.name, mime_type: record.type, size: record.size, checksum: record.checksum,
      chunk_size: record.chunk_size, resource_key: record.resource_key, operation_id: record.operation_id
    }) });
    if (!response.ok) throw new Error(`Upload session failed (${response.status})`);
    const payload = await response.json();
    return payload?.data || payload || {};
  }
  async function upload(id, options = {}) {
    const item = await get(id); if (!item) throw new Error('Attachment not found.');
    let { record, blob } = item; const controller = options.controller || new AbortController(); activeUploads.set(id, controller);
    if (root.network?.shouldPauseLargeTransfer?.(record.size) && !options.force) { record.state='paused'; record.last_error='Paused for weak or restricted network'; record.updated_at=now(); await DB().put(S().attachments,record); return record; }
    record.state='uploading'; record.updated_at=now(); await DB().put(S().attachments,record);
    try {
      if (record.upload_session_id) { record=await reconcileSession(record,controller.signal); await DB().put(S().attachments,record); }
      if (!record.upload_session_id) { const session=await createSession(record,controller.signal); record.upload_session_id=session.id; record.uploaded_bytes=session.uploaded_bytes||0; record.chunk_size=session.chunk_size||record.chunk_size; await DB().put(S().attachments,record); }
      for (let offset=record.uploaded_bytes||0; offset<blob.size; offset+=record.chunk_size) {
        const end=Math.min(blob.size,offset+record.chunk_size), chunk=blob.slice(offset,end), chunkHash=await sha256(chunk), key=`${id}:${offset}`;
        await DB().put(S().attachmentChunks,{key,attachment_id:id,offset,end,size:chunk.size,checksum:chunkHash,state:'uploading',updated_at:now()});
        const response=await fetch(root.contracts.endpoints.attachmentSession(record.upload_session_id),{method:'PUT',credentials:'same-origin',signal:controller.signal,headers:{'Content-Type':record.type || 'application/octet-stream','Content-Range':`bytes ${offset}-${end-1}/${blob.size}`,'X-Chunk-SHA256':chunkHash,'X-Attachment-UUID':record.local_uuid},body:chunk});
        if(!response.ok) throw new Error(`Chunk upload failed (${response.status})`);
        record.uploaded_bytes=end; record.updated_at=now(); await DB().put(S().attachments,record);
        await DB().put(S().attachmentChunks,{key,attachment_id:id,offset,end,size:chunk.size,checksum:chunkHash,state:'synced',updated_at:now()});
        emit('workcore:attachment-progress',{id,uploaded:end,total:blob.size,progress:end/blob.size}); options.onProgress?.(end/blob.size,record);
      }
      const chunks=await DB().byIndex(S().attachmentChunks,'by_attachment',id);
      const complete=await fetch(root.contracts.endpoints.attachmentSession(record.upload_session_id),{method:'POST',credentials:'same-origin',signal:controller.signal,headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({complete:true,checksum:record.checksum})});
      if(!complete.ok) throw new Error(`Upload completion failed (${complete.status})`);
      const completePayload=await complete.json(); const canonical=completePayload?.data||completePayload||{}; record={...record,server_id:canonical.id||canonical.server_id||record.server_id,state:'synced',uploaded_bytes:record.size,synced_at:now(),updated_at:now(),protected_until_synced:false,last_error:null};
      await DB().put(S().attachments,record); for(const chunk of chunks)await DB().remove(S().attachmentChunks,chunk.key); emit('workcore:attachment-uploaded',{attachment:record}); return record;
    } catch(error) {
      record.state=error.name==='AbortError'?'paused':'failed'; record.last_error=error.message; record.updated_at=now(); await DB().put(S().attachments,record); emit('workcore:attachment-failed',{attachment:record,error:error.message}); throw error;
    } finally { activeUploads.delete(id); }
  }
  async function resumePending(options={}) { const rows=await DB().all(S().attachments); const candidates=rows.filter(r=>['local','failed','paused'].includes(r.state)); const results=[]; for(const row of candidates){try{results.push(await upload(row.id,options))}catch(error){results.push({id:row.id,error:error.message})}} return results; }
  async function cleanupSynced({olderThanDays=30,preview=false}={}) { const cutoff=Date.now()-olderThanDays*86400000, rows=await DB().all(S().attachments), candidates=rows.filter(r=>r.state==='synced'&&new Date(r.synced_at||r.updated_at).getTime()<cutoff&&!r.pinned); if(!preview) for(const row of candidates) await remove(row.id,{force:true}); return candidates; }
  root.attachments = { save,get,remove,removeAttachmentChunks,upload,cancelUpload,resumePending,cleanupSynced,findDuplicate,validate,verifySignature,sniffMime,reconcileSession,sha256,supported:()=>Boolean(navigator.storage?.getDirectory),defaults:DEFAULTS };
})();
