<?php

use App\Services\Memory\CodeDetectionService;

test('it detects markdown fenced code blocks', function () {
    $detector = new CodeDetectionService();
    $text = "Here is some code:\n```php\necho 'hello';\n```";
    expect($detector->isCodeHeavy($text))->toBeTrue();
});

test('it detects inline code snippets', function () {
    $detector = new CodeDetectionService();
    $text = "Run `php artisan migrate` and then `npm run dev` to start the app.";
    // Two inline code blocks + some cli-like terms = easily passes threshold
    expect($detector->isCodeHeavy($text))->toBeTrue();
});

test('it detects common cli commands', function () {
    $detector = new CodeDetectionService();
    $text = "git push origin main\ndocker-compose up -d";
    expect($detector->isCodeHeavy($text))->toBeTrue();
});

test('it detects sql queries', function () {
    $detector = new CodeDetectionService();
    $text = "SELECT * FROM users WHERE active = 1 ORDER BY created_at DESC";
    expect($detector->isCodeHeavy($text))->toBeTrue();
});

test('it detects pure code pasted without fences', function () {
    $detector = new CodeDetectionService();
    $text = <<<PHP
    public function index()
    {
        \$users = User::where('active', 1)->get();
        return view('users.index', compact('users'));
    }
    PHP;
    expect($detector->isCodeHeavy($text))->toBeTrue();
});

test('it does not falsely classify ordinary prose as code', function () {
    $detector = new CodeDetectionService();
    $text = "I think we should use the new approach for the project. It will save us a lot of time. The class we discussed yesterday is a good start.";
    // The word 'class' might match, but shouldn't cross the threshold.
    expect($detector->isCodeHeavy($text))->toBeFalse();
});

test('it detects dense symbols as code', function () {
    $detector = new CodeDetectionService();
    $text = "\$a = [1, 2, 3]; \$b = \$a[0] + \$a[1]; return \$b;";
    expect($detector->isCodeHeavy($text))->toBeTrue();
});
