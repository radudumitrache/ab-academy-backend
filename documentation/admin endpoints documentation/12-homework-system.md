# Homework System

This section documents the homework system models, their relationships, and provides examples of how to use them in the AB Academy platform.

## Models Overview

The homework system consists of the following models:

1. **Homework** - Represents an assignment with details like title, description, due date, etc.
2. **Question** - Base model for all types of questions in a homework assignment
3. **MultipleChoiceQuestion** - Extends the Question model to support multiple choice questions
4. **QuestionResponse** - Records student responses to questions

## Database Schema

### Homework Table

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| homework_title | string | Title of the homework |
| homework_description | text | Description of the homework (nullable) |
| due_date | date | Deadline for submission |
| people_assigned | json | Array of student IDs assigned to this homework |
| groups_assigned | json | Array of group IDs assigned to this homework |
| date_created | timestamp | When the homework was created |
| created_at | timestamp | Laravel timestamp |
| updated_at | timestamp | Laravel timestamp |

### Questions Table

| Column | Type | Description |
|--------|------|-------------|
| question_id | bigint | Primary key |
| question_text | text | The text of the question |
| homework_id | bigint | Foreign key to homework |
| question_type | string | Type of question (basic, multiple_choice, etc.) |
| created_at | timestamp | Laravel timestamp |
| updated_at | timestamp | Laravel timestamp |

### Multiple Choice Questions Table

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| question_id | bigint | Foreign key to questions table |
| variants | json | Array of possible answers |
| correct_variant | integer | Index of the correct answer in the variants array |
| created_at | timestamp | Laravel timestamp |
| updated_at | timestamp | Laravel timestamp |

### Question Responses Table

| Column | Type | Description |
|--------|------|-------------|
| response_id | bigint | Primary key |
| related_question | bigint | Foreign key to questions table |
| related_student | bigint | Foreign key to users table (student) |
| answer | text | The student's answer |
| created_at | timestamp | Laravel timestamp |
| updated_at | timestamp | Laravel timestamp |

## Model Relationships

### Homework Model

```php
// Get all questions for a homework
$homework->questions();
```

### Question Model

```php
// Get the homework this question belongs to
$question->homework();

// Get multiple choice details (if applicable)
$question->multipleChoiceDetails();

// Get all student responses for this question
$question->responses();
```

### MultipleChoiceQuestion Model

```php
// Get the base question
$mcQuestion->question();
```

### QuestionResponse Model

```php
// Get the question this response is for
$response->question();

// Get the student who submitted this response
$response->student();
```

## Usage Examples

### Creating a Homework Assignment

```php
$homework = Homework::create([
    'homework_title' => 'Math Homework',
    'homework_description' => 'Practice problems for algebra',
    'due_date' => '2026-03-01',
    'people_assigned' => [1, 2, 3], // Student IDs
    'groups_assigned' => [1, 2] // Group IDs
]);
```

### Adding Questions to a Homework

```php
// Add a basic question
$basicQuestion = Question::create([
    'question_text' => 'Solve for x: 2x + 5 = 15',
    'homework_id' => $homework->id,
    'question_type' => 'basic'
]);

// Add a multiple choice question
$mcQuestion = Question::create([
    'question_text' => 'Which of the following is a prime number?',
    'homework_id' => $homework->id,
    'question_type' => 'multiple_choice'
]);

// Add multiple choice options
MultipleChoiceQuestion::create([
    'question_id' => $mcQuestion->question_id,
    'variants' => ['4', '7', '9', '10'],
    'correct_variant' => 1 // Index 1 corresponds to '7'
]);
```

### Recording Student Responses

```php
// Record a student's response to a basic question
QuestionResponse::create([
    'related_question' => $basicQuestion->question_id,
    'related_student' => $studentId,
    'answer' => 'x = 5'
]);

// Record a student's response to a multiple choice question
QuestionResponse::create([
    'related_question' => $mcQuestion->question_id,
    'related_student' => $studentId,
    'answer' => '1' // Index of the selected option
]);
```

### Retrieving Homework with Questions

```php
// Get a homework with all its questions
$homework = Homework::with('questions')->find($homeworkId);

// Get all multiple choice questions for a homework
$mcQuestions = Question::with('multipleChoiceDetails')
    ->where('homework_id', $homeworkId)
    ->where('question_type', 'multiple_choice')
    ->get();
```

### Checking Student Responses

```php
// Get all responses for a specific question
$responses = QuestionResponse::with('student')
    ->where('related_question', $questionId)
    ->get();

// Get all responses from a specific student
$studentResponses = QuestionResponse::with('question')
    ->where('related_student', $studentId)
    ->get();
```

## Extending the System

The homework system is designed to be extensible. To add a new question type:

1. Create a new model that extends or relates to the Question model
2. Create a migration for the new question type's specific fields
3. Define the relationship between the new model and the Question model

Example for creating an essay question type:

```php
// EssayQuestion model
class EssayQuestion extends Model
{
    protected $fillable = [
        'question_id',
        'word_limit',
        'rubric',
    ];
    
    protected $casts = [
        'rubric' => 'array',
    ];
    
    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id', 'question_id');
    }
}
```

```php
// Migration
Schema::create('essay_questions', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('question_id');
    $table->integer('word_limit')->nullable();
    $table->json('rubric')->nullable();
    $table->timestamps();
    
    $table->foreign('question_id')->references('question_id')->on('questions')->onDelete('cascade');
});
```
