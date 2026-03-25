# Survey Edit Functionality - Complete Flow Analysis

## 1. EDIT VIEW STRUCTURE

**File:** `resources/views/surveys/edit.blade.php`

### View Features:
- **Form Type:** PUT method via `@method('PUT')` to `surveys.update` route
- **Layout:** Uses `<x-app-layout>` component wrapper
- **Structure:**
  - Survey details section (title, description)
  - Questions container (`#questions-container`) - initially empty, populated by JavaScript
  - Add Question button section with 5 question type buttons
  - Cancel/Update submission buttons
  - Passes existing questions via `window.existingQuestions` JavaScript variable

### Key Difference from Create:
- Edit view passes `$existingQuestions` to the blade template
- Create view initializes `window.existingQuestions = []`
- Edit view initializes `window.existingQuestions = @json($existingQuestions)`

---

## 2. HOW EXISTING QUESTIONS ARE LOADED AND DISPLAYED

### Backend Processing (SurveyController.php - `edit()` method):

```php
$survey->load('questions.options');  // Eager load with options

$existingQuestions = $survey->questions->map(function ($q) {
    return [
        'type'        => $q->type,
        'title'       => $q->title,
        'description' => $q->description,
        'is_required' => $q->is_required,
        'settings'    => $q->settings ?? [],
        'options'     => $q->options->pluck('label')->toArray(),
    ];
})->values();
```

**Process:**
1. Survey is loaded with all related questions and their options
2. Each question is transformed to include: type, title, description, is_required, settings, and options labels
3. Serialized to JSON and passed to blade view

### Frontend Loading (question-builder.js):

```javascript
// DOMContentLoaded event listener at bottom of file:
if (window.existingQuestions && window.existingQuestions.length > 0) {
    window.existingQuestions.forEach(function (q) {
        addQuestion(q.type, q);  // Calls addQuestion() for each existing question
    });
}
```

**Process:**
1. On page load, loops through `window.existingQuestions`
2. Calls `addQuestion(type, data)` function for each question
3. Each question renders as an interactive card in the DOM

---

## 3. FORM SUBMISSION FLOW (Create vs Update)

### Create Survey (`POST /surveys`):
1. User fills survey details
2. User adds/configures questions via JavaScript
3. Form serializes question data with array format: `questions[0][type]`, `questions[0][title]`, etc.
4. `StoreSurveyRequest` validates all data
5. `SurveyController::store()` creates survey and all questions in a transaction

### Edit Survey (`PUT /surveys/{survey}`):
1. Page loads with existing survey data and questions
2. JavaScript renders all existing questions in editable form
3. User can:
   - Edit survey title/description
   - Modify existing questions (title, description, required status, options)
   - Add new questions
   - Remove questions
   - Reorder questions (using up/down arrows)
4. Form serializes with same array format
5. `StoreSurveyRequest` validates
6. `SurveyController::update()` **DELETES ALL OLD QUESTIONS** and recreates them from the form data
7. Firebase transaction ensures atomicity

### Important Note About Updates:
```php
// DELETE OLD QUESTIONS AND RECREATE
$survey->questions()->delete();

foreach ($validated['questions'] as $index => $questionData) {
    $question = $survey->questions()->create([
        'type'        => $questionData['type'],
        'title'       => $questionData['title'],
        'description' => $questionData['description'] ?? null,
        'is_required' => $questionData['is_required'] ?? true,
        'order'       => $index,     // ← ORDER SET BY ARRAY INDEX
        'settings'    => $questionData['settings'] ?? null,
    ]);

    if (!empty($questionData['options'])) {
        foreach ($questionData['options'] as $optIndex => $label) {
            $question->options()->create([
                'label' => $label,
                'order' => $optIndex,
            ]);
        }
    }
}
```

**Implication:** The current approach does not preserve question IDs. This could be problematic if responses already exist for those questions.

---

## 4. QUESTION ORDER/POSITION HANDLING IN DATABASE

### Database Schema:

**`questions` table:**
```sql
- `id` - primary key
- `survey_id` - foreign key
- `type` - enum (true_false, multiple_choice, ranking, rating, open_ended)
- `title` - string
- `description` - nullable text
- `is_required` - boolean (default: true)
- `order` - unsignedInteger (default: 0)  ← ORDERING COLUMN
- `settings` - json (nullable)
- `created_at`, `updated_at`
- INDEX: ['survey_id', 'order']
```

**`question_options` table:**
```sql
- `id` - primary key
- `question_id` - foreign key
- `label` - string
- `order` - unsignedInteger  ← OPTION ORDERING
- Index on ['question_id', 'order']
```

### How Ordering Works:

1. **During Create/Update:**
   - Questions are ordered by their position in the form array
   - `$index` in the foreach loop becomes the `order` value
   - Question 0 has order=0, Question 1 has order=1, etc.

2. **On Retrieval:**
   - Model uses `->orderBy('order')` implicitly via the index
   - Blade passes questions in their default ordering

3. **JavaScript Handling:**
   - `moveQuestion(idx, direction)` function allows up/down arrow buttons
   - Moves questions in the DOM using `insertBefore()`
   - **Does NOT change database order** until form is submitted
   - On submit, the array indices become the new order values

### Current Limitation:
There's **no persistent drag-and-drop** functionality. The up/down arrows work only on the current page session. If you refresh before submitting, reordering is lost.

---

## 5. EXISTING DRAG-AND-DROP FUNCTIONALITY

### Current State: **NO PRODUCTION DRAG-AND-DROP**

**What EXISTS:**
- Simple up/down arrow buttons (`↑` and `↓`) for reordering
- Functions in `question-builder.js`:
  - `moveQuestion(idx, direction)` - handles DOM repositioning
  - Supports moving questions up (-1) or down (+1)
  - Works in real-time on the page

**What's MISSING:**
- No HTML5 drag-and-drop attributes (draggable, @drop, @dragover)
- No library integration (SortableJS, Interactjs, Dnd-kit, etc.)
- No visual drag feedback (cursor, highlights, drop zones)
- No AJAX persistence of order changes

### The JavaScript Implementation:

```javascript
function moveQuestion(idx, direction) {
    const container = document.getElementById('questions-container');
    const card = document.getElementById('question-card-' + idx);
    if (!card) return;

    const cards = Array.from(container.children);
    const currentIndex = cards.indexOf(card);
    const targetIndex = currentIndex + direction;

    if (targetIndex < 0 || targetIndex >= cards.length) return;

    if (direction === -1) {
        container.insertBefore(card, cards[targetIndex]);
    } else {
        container.insertBefore(card, cards[targetIndex].nextSibling);
    }
    reindexQuestions();  // ← Currently does nothing
}
```

---

## 6. FORM DATA STRUCTURE DURING SUBMISSION

### Example of serialized form data when submitting:

```
title: "Customer Satisfaction"
description: "Q1 2026 feedback"

questions[0][type]: "multiple_choice"
questions[0][title]: "How satisfied are you?"
questions[0][description]: "Rate your overall satisfaction"
questions[0][is_required]: "1"
questions[0][settings][allow_multiple]: "1"
questions[0][options][]: "Very Satisfied"
questions[0][options][]: "Satisfied"
questions[0][options][]: "Neutral"
questions[0][options][]: "Unsatisfied"

questions[1][type]: "rating"
questions[1][title]: "Would you recommend us?"
questions[1][description]: ""
questions[1][is_required]: "1"
questions[1][settings][max_rating]: "5"
```

### Array Index = Order:
The position in the form array automatically becomes the question order in the database.

---

## SUMMARY TABLE

| Aspect | Details |
|--------|---------|
| **Edit View File** | `resources/views/surveys/edit.blade.php` |
| **Controller Method** | `SurveyController::edit()` loads and `update()` saves |
| **Question Rendering** | JavaScript dynamically creates form inputs from `window.existingQuestions` |
| **Form Route** | PUT `surveys.update` |
| **Validation** | `StoreSurveyRequest` validates all question data |
| **Database Order Column** | `questions.order` (unsignedInteger) |
| **Reordering UI** | Up/Down arrow buttons (↑↓) in each question card |
| **Reordering Persistence** | Only on form submit; refreshing page loses changes |
| **Drag-and-Drop** | **NOT IMPLEMENTED** - only simple arrow buttons |
| **On Update** | Old questions deleted, new ones recreated from form data |
| **Transaction** | Yes - `DB::transaction()` ensures atomicity |

---

## POTENTIAL IMPROVEMENTS IDENTIFIED

1. **Preserve Question IDs on Update** - Currently recreates all questions, losing IDs and breaking response relationships
2. **Add Proper Drag-and-Drop** - Could integrate SortableJS or similar libraries
3. **Persist Order Dynamically** - Could add AJAX endpoint to update order without full form submission
4. **Better Reordering UX** - Visual feedback, drag handles, live preview
5. **Soft Deletes** - For audit trail of deleted questions
