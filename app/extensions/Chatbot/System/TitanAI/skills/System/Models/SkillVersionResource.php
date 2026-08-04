<?php

declare(strict_types=1);
namespace App\Extensions\SystemAIChatSkills\System\Models;
use App\Extensions\SystemAIChatSkills\System\Packages\SkillResourceFingerprint;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SkillVersionResource extends Model
{
    protected $table='skill_version_resources';
    protected $fillable=['skill_version_id','path','sha256','size','mime_type'];
    protected $casts=['size'=>'integer'];
    public function version(): BelongsTo { return $this->belongsTo(SkillVersion::class,'skill_version_id'); }
    public function fingerprint(): SkillResourceFingerprint { return SkillResourceFingerprint::fromHash($this->path,$this->sha256,(int)$this->size,$this->mime_type); }
    public function getFileExtension(): string { return strtolower(pathinfo($this->path,PATHINFO_EXTENSION)); }
    public function getFileName(): string { return pathinfo($this->path,PATHINFO_BASENAME); }
    public function getDirectory(): string { $d=pathinfo($this->path,PATHINFO_DIRNAME); return $d==='.'?'':$d; }
    public function isImage(): bool { return str_starts_with((string)$this->mime_type,'image/'); }
    public function isText(): bool { return str_starts_with((string)$this->mime_type,'text/'); }
    public function isScript(): bool { return in_array($this->getFileExtension(),['py','js','ts','sh','bash','php','rb','go','rs','sql'],true); }
    public function getFormattedSize(): string { $units=['B','KB','MB','GB','TB'];$size=(float)$this->size;$i=0;while($size>=1024&&$i<count($units)-1){$size/=1024;$i++;}return round($size,2).' '.$units[$i]; }
    public function getMimeTypeIcon(): string { return $this->isImage()?'image':($this->isText()?'file-alt':($this->isScript()?'code':'file')); }
}
