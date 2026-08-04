export const createDeviceAgents = () => [
  { id: 'conversation-agent', events: ['conversation:created','message:queued'], offline: true, async handle(e,{events}) { await events.emit('agent:conversation', e); return { handled: true }; } },
  { id: 'knowledge-agent', events: ['knowledge:search'], offline: true, async handle(e,{db}) { return db?.searchKnowledge ? db.searchKnowledge(e.query) : []; } },
  { id: 'job-assistant', events: ['job:opened','job:command'], offline: true, async handle(e,{events}) { await events.emit('agent:job', e); return { provisional: true }; } },
  { id: 'reminder-agent', events: ['reminder:schedule'], offline: true, async handle(e,{events}) { await events.emit('notification:local', e); return { scheduled: true }; } },
  { id: 'sync-agent', events: ['network:online','sync:requested','outbox:changed'], offline: true, async handle(e,{outbox,connectivity}) { if (!connectivity?.isReachable?.()) return { deferred: true }; return outbox?.flush ? outbox.flush() : { deferred: true }; } },
  { id: 'attachment-agent', events: ['attachment:added'], offline: true, async handle(e,{events}) { await events.emit('attachment:prepare', e); return { queued: true }; } }
];
