> ## Documentation Index
> Fetch the complete documentation index at: https://docs.typesafe.ai/llms.txt
> Use this file to discover all available pages before exploring further.

# API reference

> Full HTTP API reference for the TypeSafe evaluation endpoint.

Evaluate a `state` against a map of typed `questions` and get back structured `answers`, one per question. For a guided introduction, start with the [primitives](/primitives).

## Evaluation endpoint

```http theme={null}
POST https://api.typesafe.ai/v1/systemone
Authorization: Bearer <API_KEY>
Content-Type: application/json
```

## Request body

The top-level shape of every request. Each entry in the `questions` map is a typed question you name.

<ParamField body="state" type="string | object | array" required>
  The content to evaluate. A plain string for text, or structured data (object/array) for things like chat logs, records, or the current state of your application. See [State](/concepts/state) for formats and best practices.
</ParamField>

<ParamField body="model" type="string" required>
  The model that handles the request. Use `"jev-latest"`, TypeSafe's flagship model. See [Models](/models) for the available models and aliases.
</ParamField>

<ParamField body="questions" type="map<string, Question>" required>
  A map of typed [Question](#question-types) objects. You choose each key; answers come back under the same keys.

  <Expandable title="map entries">
    <ParamField body="‹question id›" type="Question">
      A key you choose. The matching [Answer](#answer-types) is returned under this same id. The key is not sent to the underlying model and is not used in inference.
    </ParamField>
  </Expandable>
</ParamField>

```json Example request theme={null}
{
  "state": "Help! My payouts have been failing for 3 days.",
  "model": "jev-latest",
  "questions": {
    "is_urgent": {
      "type": "noul",
      "instructions": "Does this convey urgency?"
    }
  }
}
```

## Question types

A `Question` is one of three types, set by its `type` field. All three share `type` and `instructions`; each adds its own `criteria`.

The `instructions` property can be a string, an object, or an array. You can break up a long question that has extra context, or data it needs to reference, into a structured object. Put the question in one field and the data in the others, and refer to the data fields by name in backticks, the same way you point a question at a nested `state` value:

```json theme={null}
"instructions": {
  "potential_duplicate": {
    "name": "John Smith",
    "location": "Oakland, California",
    "last_employer": "Google"
  },
  "question": "Is the resume for the same person as `potential_duplicate`?"
}
```

See [Use structure in the questions](/concepts/how-to-build-with-system-one#use-structure-in-the-questions) to learn more.

### Noul

A yes/no question. Returns the probability the answer is yes.

<ParamField body="type" type="&#x22;noul&#x22;" required />

<ParamField body="instructions" type="string | object | array" required>
  The yes/no question to evaluate. An object can hold the question in one field and data it refers to in others; see [Use structure in the questions](/concepts/how-to-build-with-system-one#use-structure-in-the-questions).
</ParamField>

<ParamField body="criteria" type="object">
  Optional descriptions of what a yes and a no mean.

  <Expandable title="properties">
    <ParamField body="true" type="string | object | array">
      What a yes (value near 1) means.
    </ParamField>

    <ParamField body="false" type="string | object | array">
      What a no (value near 0) means.
    </ParamField>
  </Expandable>
</ParamField>

```json Example request focus={5-12} theme={null}
{
  "state": "Help! My payouts have been failing for 3 days.",
  "model": "jev-latest",
  "questions": {
    "is_urgent": {
      "type": "noul",
      "instructions": "Does this convey urgency?",
      "criteria": {
        "true": "Explicitly time-sensitive",
        "false": "No urgency expressed"
      }
    }
  }
}
```

### Choice

Picks one option from a set you define. Returns the chosen option and the full probability distribution.

<ParamField body="type" type="&#x22;choice&#x22;" required />

<ParamField body="instructions" type="string | object | array" required>
  What the model should decide. An object can hold the question in one field and data it refers to in others; see [Structured instructions and criteria](/primitives/choice#structured-instructions-and-criteria).
</ParamField>

<ParamField body="criteria" type="map<string, string | object | array | null>" required>
  A map of option to rubric description; use null when an option needs no extra detail. You can have a maximum of 255 options per Choice.

  <Expandable title="map entries">
    <ParamField body="‹option›" type="string | object | array | null">
      A key you choose. A description of this option.
    </ParamField>
  </Expandable>
</ParamField>

```json Example request focus={5-13} theme={null}
{
  "state": "Help! My payouts have been failing for 3 days.",
  "model": "jev-latest",
  "questions": {
    "department": {
      "type": "choice",
      "instructions": "Which team should handle this?",
      "criteria": {
        "billing": "Payments, invoicing, refunds",
        "technical": "Bugs, outages, integrations",
        "sales": "Pricing, upgrades, new accounts"
      }
    }
  }
}
```

### Score

Rates the state along a rubric you define. Returns a probability-weighted value across your levels.

<ParamField body="type" type="&#x22;score&#x22;" required />

<ParamField body="instructions" type="string | object | array" required>
  What the model should rate. An object can hold the question in one field and data it refers to in others; see [Use structure in the questions](/concepts/how-to-build-with-system-one#use-structure-in-the-questions).
</ParamField>

<ParamField body="criteria" type="array<string | object | array>" required>
  An ordered array of level descriptions. A Score should have at least two levels; the API accepts up to 10.
</ParamField>

```json Example request focus={5-9} theme={null}
{
  "state": "Help! My payouts have been failing for 3 days.",
  "model": "jev-latest",
  "questions": {
    "frustration": {
      "type": "score",
      "instructions": "How frustrated is the customer?",
      "criteria": ["Calm", "Frustrated", "Very angry"]
    }
  }
}
```

## Response body

One answer per question, returned under the same ids you provided.

<ResponseField name="model" type="string" required>
  The model that performed the evaluation.
</ResponseField>

<ResponseField name="answers" type="map<string, Answer>" required>
  One [Answer](#answer-types) per question, keyed by the same ids you used in questions.

  <Expandable title="map entries">
    <ResponseField name="‹question id›" type="Answer">
      The same id you chose in questions.
    </ResponseField>
  </Expandable>
</ResponseField>

<ResponseField name="usage" type="object" required>
  Token usage for the request.

  <Expandable title="properties">
    <ResponseField name="input_tokens" type="integer" />

    <ResponseField name="output_tokens" type="integer" />
  </Expandable>
</ResponseField>

```json Example response theme={null}
{
  "model": "jev-1.13.0",
  "answers": {
    "is_urgent": {
      "type": "noul",
      "noul": 0.95
    }
  },
  "usage": { "input_tokens": 296, "output_tokens": 20 }
}
```

## Answer types

Every answer carries a `type` matching its question. Choice and Score answers also carry a `confidence` between 0 to 1, derived from the answer's probability distribution. See [Confidence](/confidence).

### Noul answer

<ResponseField name="type" type="&#x22;noul&#x22;" required />

<ResponseField name="noul" type="number" required>
  The yes/no answer on a scale from 0 (no) to 1 (yes).
</ResponseField>

```json Example response focus={4-7} theme={null}
{
  "model": "jev-1.13.0",
  "answers": {
    "is_urgent": {
      "type": "noul",
      "noul": 0.95
    }
  },
  "usage": { "input_tokens": 307, "output_tokens": 20 }
}
```

### Choice answer

<ResponseField name="type" type="&#x22;choice&#x22;" required />

<ResponseField name="choice" type="string" required>
  The highest-probability option.
</ResponseField>

<ResponseField name="probabilities" type="map<string, number>" required>
  Every option mapped to its probability (floats that sum to 1).

  <Expandable title="map entries">
    <ResponseField name="‹option›" type="number">
      An option you defined in criteria.
    </ResponseField>
  </Expandable>
</ResponseField>

<ResponseField name="confidence" type="number" required>
  How certain the model is, derived from probabilities.
</ResponseField>

```json Example response focus={4-9} theme={null}
{
  "model": "jev-1.13.0",
  "answers": {
    "department": {
      "type": "choice",
      "choice": "billing",
      "probabilities": { "billing": 0.88, "technical": 0.12, "sales": 0.0 },
      "confidence": 0.81
    }
  },
  "usage": { "input_tokens": 318, "output_tokens": 34 }
}
```

### Score answer

<ResponseField name="type" type="&#x22;score&#x22;" required />

<ResponseField name="score" type="number" required>
  The probability-weighted answer across the levels; can land between levels.
</ResponseField>

<ResponseField name="legend" type="map<string, string>" required>
  Each level number mapped back to its description.
</ResponseField>

<ResponseField name="probabilities" type="map<string, number>" required>
  Each level (string key) mapped to its probability (floats that sum to 1).

  <Expandable title="map entries">
    <ResponseField name="‹level›" type="number">
      A level index, as a string key matching legend.
    </ResponseField>
  </Expandable>
</ResponseField>

<ResponseField name="confidence" type="number" required>
  How certain the model is, derived from probabilities.
</ResponseField>

```json Example response focus={4-10} theme={null}
{
  "model": "jev-1.13.0",
  "answers": {
    "frustration": {
      "type": "score",
      "score": 1.05,
      "legend": { "0": "Calm", "1": "Frustrated", "2": "Very angry" },
      "probabilities": { "0": 0.0, "1": 0.95, "2": 0.05 },
      "confidence": 0.92
    }
  },
  "usage": { "input_tokens": 304, "output_tokens": 18 }
}
```

## Errors

Errors use standard HTTP status codes with a JSON body describing what went wrong.

| Status                     | Meaning                                                                                                                                  |
| -------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------- |
| `401 Unauthorized`         | Missing or invalid API key. Check the `Authorization` header.                                                                            |
| `422 Unprocessable Entity` | The request body failed validation — for example a missing required field or a malformed question. The body details the offending field. |
| `429 Too Many Requests`    | You have exceeded your rate limit. Back off and retry after a short delay.                                                               |
| `529 Overloaded`           | TypeSafe is temporarily overloaded. Retry after a short delay.                                                                           |

### Handling rate limits

When you receive a `429 Too Many Requests` or `529 Overloaded` response, retry the request with exponential backoff instead of retrying immediately. Our client SDKs handle this automatically, so no extra handling is needed if you use one of our SDKs with its default retry policy.
