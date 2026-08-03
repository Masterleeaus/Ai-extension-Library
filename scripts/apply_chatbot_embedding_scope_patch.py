#!/usr/bin/env python3
"""Apply the Chatbot embedding ownership fix after core overlay materialisation."""

from __future__ import annotations

from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]


def replace_once(path: Path, old: str, new: str) -> bool:
    content = path.read_text(encoding="utf-8")

    if new in content:
        return False

    occurrences = content.count(old)
    if occurrences != 1:
        raise RuntimeError(
            f"Expected exactly one patch target in {path}, found {occurrences}"
        )

    path.write_text(content.replace(old, new, 1), encoding="utf-8")
    return True


def main() -> None:
    controller = ROOT / "extensions/Chatbot/System/Http/Controllers/ChatbotTrainController.php"
    request = ROOT / "extensions/Chatbot/System/Http/Requests/Train/EmbedingRequest.php"

    controller_changed = replace_once(
        controller,
        """        $embeddings = ChatbotEmbedding::query()\n            ->whereNull('embedding')\n            ->whereIn('id', $data)\n            ->get();""",
        """        $embeddings = $chatbot->embeddings()\n            ->whereNull('embedding')\n            ->whereIn('id', $data)\n            ->get();""",
    )

    request_changed = replace_once(
        request,
        """use Illuminate\\Foundation\\Http\\FormRequest;\n\nclass EmbedingRequest extends FormRequest""",
        """use Illuminate\\Foundation\\Http\\FormRequest;\nuse Illuminate\\Validation\\Rule;\n\nclass EmbedingRequest extends FormRequest""",
    )

    request_rule_changed = replace_once(
        request,
        """            'data.*' => 'required|exists:' . (new ChatbotEmbedding)->getTable() . ',id',""",
        """            'data.*' => [\n                'required',\n                Rule::exists((new ChatbotEmbedding)->getTable(), 'id')\n                    ->where(fn ($query) => $query->where('chatbot_id', $this->input('id'))),\n            ],""",
    )

    changed = controller_changed or request_changed or request_rule_changed
    print("Applied Chatbot embedding ownership patch." if changed else "Chatbot embedding ownership patch already applied.")


if __name__ == "__main__":
    main()
