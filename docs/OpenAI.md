# API de OpenAI


## Modelos

**Docs:**: https://platform.openai.com/docs/api-reference/models/list


Se pueden listar los modelos usando:

```bash
curl https://api.openai.com/v1/models \
  -H "Authorization: Bearer $OPENAI_API_KEY"
```

```json
{
    "object": "list",
    "data": [
        {
            "id": "dall-e-3",
            "object": "model",
            "created": 1698785189,
            "owned_by": "system"
        },
        {
            "id": "dall-e-2",
            "object": "model",
            "created": 1698798177,
            "owned_by": "system"
        },
        {
            "id": "gpt-3.5-turbo-0125",
            "object": "model",
            "created": 1706048358,
            "owned_by": "system"
        },
        {
            "id": "text-embedding-ada-002",
            "object": "model",
            "created": 1671217299,
            "owned_by": "openai-internal"
        },
        {
            "id": "tts-1-hd-1106",
            "object": "model",
            "created": 1699053533,
            "owned_by": "system"
        },
        {
            "id": "text-embedding-3-small",
            "object": "model",
            "created": 1705948997,
            "owned_by": "system"
        },
        {
            "id": "tts-1-hd",
            "object": "model",
            "created": 1699046015,
            "owned_by": "system"
        },
        {
            "id": "gpt-4-0125-preview",
            "object": "model",
            "created": 1706037612,
            "owned_by": "system"
        },
        {
            "id": "gpt-4-turbo-preview",
            "object": "model",
            "created": 1706037777,
            "owned_by": "system"
        },
        {
            "id": "text-embedding-3-large",
            "object": "model",
            "created": 1705953180,
            "owned_by": "system"
        },
        {
            "id": "whisper-1",
            "object": "model",
            "created": 1677532384,
            "owned_by": "openai-internal"
        },
        {
            "id": "babbage-002",
            "object": "model",
            "created": 1692634615,
            "owned_by": "system"
        },
        {
            "id": "gpt-3.5-turbo-16k-0613",
            "object": "model",
            "created": 1685474247,
            "owned_by": "openai"
        },
        {
            "id": "davinci-002",
            "object": "model",
            "created": 1692634301,
            "owned_by": "system"
        },
        {
            "id": "gpt-3.5-turbo-16k",
            "object": "model",
            "created": 1683758102,
            "owned_by": "openai-internal"
        },
        {
            "id": "gpt-4-0613",
            "object": "model",
            "created": 1686588896,
            "owned_by": "openai"
        },
        {
            "id": "gpt-4",
            "object": "model",
            "created": 1687882411,
            "owned_by": "openai"
        },
        {
            "id": "gpt-4-1106-preview",
            "object": "model",
            "created": 1698957206,
            "owned_by": "system"
        },
        {
            "id": "gpt-3.5-turbo",
            "object": "model",
            "created": 1677610602,
            "owned_by": "openai"
        },
        {
            "id": "gpt-4-vision-preview",
            "object": "model",
            "created": 1698894917,
            "owned_by": "system"
        },
        {
            "id": "gpt-3.5-turbo-0613",
            "object": "model",
            "created": 1686587434,
            "owned_by": "openai"
        },
        {
            "id": "gpt-3.5-turbo-1106",
            "object": "model",
            "created": 1698959748,
            "owned_by": "system"
        },
        {
            "id": "gpt-3.5-turbo-0301",
            "object": "model",
            "created": 1677649963,
            "owned_by": "openai"
        },
        {
            "id": "tts-1-1106",
            "object": "model",
            "created": 1699053241,
            "owned_by": "system"
        },
        {
            "id": "gpt-3.5-turbo-instruct",
            "object": "model",
            "created": 1692901427,
            "owned_by": "system"
        },
        {
            "id": "tts-1",
            "object": "model",
            "created": 1681940951,
            "owned_by": "openai-internal"
        },
        {
            "id": "gpt-3.5-turbo-instruct-0914",
            "object": "model",
            "created": 1694122472,
            "owned_by": "system"
        }
    ]
}
```


Se pueden obtener un solo modelo usando:

```bash
curl https://api.openai.com/v1/models/$MODEL_NAME \
  -H "Authorization: Bearer $OPENAI_API_KEY"
```

```json
{
    "id": "gpt-3.5-turbo-instruct",
    "object": "model",
    "created": 1686935002,
    "owned_by": "openai"
}
```


## Asistentes

**Docs:**: https://platform.openai.com/docs/api-reference/assistants/createAssistant


Se pueden crear los asistentes con:

```bash
curl -X POST "https://api.openai.com/v1/assistants" \
    -H "Authorization: Bearer $OPENAI_API_KEY" \
    -H "Content-Type: application/json" \
    -H "OpenAI-Beta: assistants=v1" \
    -d '{
        "name": "Math Tutor",
        "description": "Math Tutor",
        "instructions": "You are a personal math tutor. When asked a question, write and run Python code to answer the question.",
        "tools": [{"type": "retrieval"}],
        "model": "gpt-4"
    }'
```

```json
{
    "id": "asst_abc123",
    "object": "assistant",
    "created_at": 1698984975,
    "name": "Math Tutor",
    "description": null,
    "model": "gpt-4",
    "instructions": "You are a personal math tutor. When asked a question, write and run Python code to answer the question.",
    "tools": [
        {
            "type": "retrieval"
        }
    ],
    "file_ids": [],
    "metadata": {}
}
```


Se pueden eliminar los asistentes con:

```bash
curl -X DELETE "https://api.openai.com/v1/assistants/asst_abc123" \
    -H "Authorization: Bearer $OPENAI_API_KEY" \
    -H "Content-Type: application/json" \
    -H "OpenAI-Beta: assistants=v1"
```

```json
{
    "id": "asst_abc123",
    "object": "assistant.deleted",
    "deleted": true
}
```


## Corridas

**Docs:** https://platform.openai.com/docs/api-reference/runs/getRun


Se pueden obtener los datos con:

```bash
curl https://api.openai.com/v1/threads/thread_abc123/runs/run_abc123 \
    -H "Authorization: Bearer $OPENAI_API_KEY" \
    -H "OpenAI-Beta: assistants=v1"
```
