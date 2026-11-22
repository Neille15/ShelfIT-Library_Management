<?php
declare(strict_types=1);

function staffForm(string $action): void
{
    ?>
    <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
    <input type="hidden" name="action" value="<?= h($action) ?>">
    <?php
}

function staffUrl(string $view = 'studies', array $params = []): string
{
    return url('index.php', ['view' => $view, ...$params]);
}

function staffDone(string $message, string $target): never
{
    $_SESSION['notice'] = $message;
    redirect($target);
}


function staffProcessUrl(string $view, int $studyId = 0): string
{
    return url('process.php', ['view' => staffView($view), 'id' => $studyId ?: null]);
}

function renderStaffPage(array $account, string $error = '', string $action = ''): void
{
    $view = staffView(input($_GET, 'view'));
    $studyId = id($_GET['id'] ?? null);
    if ($error !== '') {
        // Derive the error destination from the action, never an arbitrary include path.
        $view = match ($action) {
            'study-save', 'study-archive', 'study-restore', 'study-delete', 'copy-save', 'copy-delete' => 'study',
            'tag-save', 'tag-delete' => 'tags',
            'location-save', 'location-delete' => 'locations',
            'account-save', 'recovery-codes-generate' => 'account',
            default => $view,
        };
        if ($view === 'study') {
            $studyId = id($_POST['study_id'] ?? null);
        }
    }
    $includeArchived = input($_GET, 'archived') === '1';
    ['record' => $record, 'studyMissing' => $studyMissing, 'staffStudies' => $staffStudies,
        'tags' => $tags, 'assigned' => $assigned, 'copies' => $copies, 'layout' => $layout]
        = staffPageData($view, $studyId, $includeArchived);
    if ($studyMissing && $error === '') {
        http_response_code(404);
    }
    $notice = $_SESSION['notice'] ?? '';
    $recoveryCount = $view === 'account' ? recoveryCodeCount($account['id']) : 0;
    unset($_SESSION['notice']);
    require_once __DIR__ . '/../../templates/staff/forms.php';
    require __DIR__ . '/../../templates/staff/page.php';
}
