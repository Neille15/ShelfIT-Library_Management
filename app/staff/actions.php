<?php
declare(strict_types=1);

function handleStaffAction(string $action, array $account): never
{
    switch ($action) {
        case 'logout':
            $_SESSION = [];
            session_destroy();
            redirect('index.php');
        case 'account-save':
            saveStaffAccount($account);
        case 'study-save':
            saveStudy();
        case 'study-archive':
        case 'study-restore':
            setStudyArchived($action === 'study-archive');
        case 'study-delete':
            deleteArchivedStudy(id($_POST['study_id'] ?? null));
        case 'copy-save':
            saveCopy();
        case 'copy-delete':
            deleteCopy();
        case 'tag-save':
            saveSubject();
        case 'tag-delete':
            deleteSubject();
        case 'location-save':
            saveLocation();
        case 'location-delete':
            deleteLocation();
    }
    throw new StaffValidation('Unknown staff action.');
}
