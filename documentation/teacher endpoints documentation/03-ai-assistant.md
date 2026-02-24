# AI Assistant

The AI assistant provides a text translation feature powered by the Anthropic Claude API.
It supports two target languages and two translation profiles (normal and medical).

---

## Translate Text

Translates a block of text into the requested language using a Claude model.
The `profile` field controls the system prompt used — medical translation uses
specialised medical terminology and phrasing.

- **URL**: `/api/teacher/ai-assistant/translate`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  Content-Type: application/json
  ```
- **Request Body**:
  ```json
  {
    "text": "De patiënt heeft koorts en hoofdpijn.",
    "target_language": "english",
    "profile": "medical"
  }
  ```
- **Field Notes**:

  | Field | Type | Required | Allowed values |
  |-------|------|----------|----------------|
  | `text` | string | Yes | Any text, max 5000 characters |
  | `target_language` | string | Yes | `english`, `dutch` |
  | `profile` | string | Yes | `normal`, `medical` |

- **Success Response** `200`:
  ```json
  {
    "message": "Translation successful",
    "data": {
      "translated_text": "The patient has a fever and headache.",
      "target_language": "english",
      "profile": "medical",
      "model": "claude-3-5-sonnet-latest"
    }
  }
  ```

- **Error Responses**:
  - **422** — validation failed:
    ```json
    {
      "message": "The given data was invalid.",
      "errors": {
        "text": ["The text field is required."],
        "target_language": ["The selected target language is invalid."],
        "profile": ["The selected profile is invalid."]
      }
    }
    ```
  - **502** — Claude API unreachable or returned no content:
    ```json
    {
      "message": "Translation request failed.",
      "error": "Connection error details"
    }
    ```
    or
    ```json
    {
      "message": "Translation response did not contain translated text."
    }
    ```
  - **500** — runtime error:
    ```json
    {
      "message": "Error details"
    }
    ```

---

## Translation Profiles

### `normal`

General-purpose translation. Uses a standard language assistant prompt with no
domain-specific instructions.

**Example use case**: Translating general correspondence, notes, or instructions.

### `medical`

Medical-domain translation. Uses a specialised system prompt that:
- Preserves medical terminology accurately
- Handles anatomical terms, drug names, and clinical language
- Targets the appropriate formal register for healthcare communication

**Example use case**: Translating patient notes, discharge summaries, or medical reports.

---

## Supported Languages

| Value | Language |
|-------|----------|
| `english` | English |
| `dutch` | Dutch (Nederlands) |

---

## Configuration

The Claude model used is controlled by the environment variable:

```
ANTHROPIC_MODEL=claude-3-5-sonnet-latest
```

Set in `config/services.php`:

```php
'anthropic' => [
    'model' => env('ANTHROPIC_MODEL', 'claude-3-5-sonnet-latest'),
],
```

The maximum output length is capped at **600 tokens** per request.
For longer texts, split the content into smaller chunks before sending.
