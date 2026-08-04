export const NODE_TYPES = {
    TRIGGER: 'trigger',
    DELAY: 'delay',
    ACTION: 'action',
};

export const DEFAULT_POSITIONS = {
    trigger: { x: 100, y: 200 },
    delay: { x: 500, y: 200 },
    action: { x: 900, y: 200 },
    actionNoDelay: { x: 550, y: 200 },
};

export const ACTION_TYPES = ['text', 'button', 'image', 'quick_replies', 'delay'];

export const VARIABLES = ['{first_name}', '{username}', '{comment_text}'];
