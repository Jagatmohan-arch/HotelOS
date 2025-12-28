
// ============================================
// Shift Functions (Phase F)
// ============================================

function renderShiftsPage(Auth $auth): void
{
    $user = $auth->user();
    $csrfToken = $auth->csrfToken();
    
    $handler = new \HotelOS\Handlers\ShiftHandler();
    $currentShift = $handler->getCurrentShift((int)$user['id']);
    
    // If shift is open, get details
    $ledgerEntries = [];
    $expectedCash = 0.0;
    
    if ($currentShift) {
        $ledgerEntries = $handler->getShiftLedger((int)$currentShift['id']);
        $expectedCash = $handler->getExpectedCash((int)$user['id'], (int)$currentShift['id'], $currentShift['shift_start_at']);
    }
    
    $recentShifts = $handler->getRecentShifts(5);
    
    $title = 'Shift Handover';
    $currentRoute = 'shifts';
    $breadcrumbs = [['label' => 'Shift Handover']];
    
    ob_start();
    include VIEWS_PATH . '/shifts/index.php';
    $content = ob_get_clean();
    
    include VIEWS_PATH . '/layouts/app.php';
}

function handleShiftStart(Auth $auth): void
{
    $handler = new \HotelOS\Handlers\ShiftHandler();
    $openingCash = (float)($_POST['opening_cash'] ?? 0);
    
    $result = $handler->startShift((int)$auth->user()['id'], $openingCash);
    
    if ($result['success']) {
        $_SESSION['flash_success'] = $result['message'];
    } else {
        $_SESSION['flash_error'] = $result['message'];
    }
    
    header('Location: /shifts');
    exit;
}

function handleShiftEnd(Auth $auth): void
{
    $handler = new \HotelOS\Handlers\ShiftHandler();
    $user = $auth->user();
    $currentShift = $handler->getCurrentShift((int)$user['id']);
    
    if (!$currentShift) {
        header('Location: /shifts');
        exit;
    }
    
    $closingCash = (float)($_POST['closing_cash'] ?? 0);
    $handoverTo = !empty($_POST['handover_to']) ? (int)$_POST['handover_to'] : null;
    $notes = $_POST['notes'] ?? '';
    
    $result = $handler->endShift(
        (int)$currentShift['id'], 
        (int)$user['id'], 
        $closingCash, 
        $handoverTo, 
        $notes
    );
    
    if ($result['success']) {
        $_SESSION['flash_success'] = $result['message'];
    } else {
        $_SESSION['flash_error'] = $result['message'];
    }
    
    header('Location: /shifts');
    exit;
}

function handleLedgerAdd(Auth $auth): void
{
    $handler = new \HotelOS\Handlers\ShiftHandler();
    $user = $auth->user();
    $currentShift = $handler->getCurrentShift((int)$user['id']);
    
    if (!$currentShift) {
        header('Location: /shifts');
        exit;
    }
    
    $type = $_POST['type'] ?? 'expense';
    $amount = (float)($_POST['amount'] ?? 0);
    $category = $_POST['category'] ?? 'Other';
    $desc = $_POST['description'] ?? '';
    
    $success = $handler->addLedgerEntry(
        (int)$user['id'], 
        (int)$currentShift['id'], 
        $type, 
        $amount, 
        $category, 
        $desc
    );
    
    if ($success) {
        $_SESSION['flash_success'] = 'Entry added to ledger.';
    } else {
        $_SESSION['flash_error'] = 'Failed to add entry.';
    }
    
    header('Location: /shifts');
    exit;
}

function renderAdminShiftsPage(Auth $auth): void
{
    $csrfToken = $auth->csrfToken();
    $handler = new \HotelOS\Handlers\ShiftHandler();
    
    $page = (int)($_GET['page'] ?? 1);
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    $shifts = $handler->getAllClosedShifts($limit, $offset);
    
    $title = 'Audit Shifts';
    $currentRoute = 'admin/shifts';
    $breadcrumbs = [['label' => 'Audit Shifts']];
    
    ob_start();
    include VIEWS_PATH . '/admin/shifts/index.php';
    $content = ob_get_clean();
    
    include VIEWS_PATH . '/layouts/app.php';
}

function handleShiftVerify(Auth $auth): void
{
    $handler = new \HotelOS\Handlers\ShiftHandler();
    // Assuming manager role check is done in routes or middleware, but let's be safe
    if (!in_array($auth->user()['role'], ['owner', 'manager'])) {
        header('Location: /dashboard');
        exit;
    }
    
    $shiftId = (int)$_POST['shift_id'];
    $notes = $_POST['manager_note'] ?? '';
    
    $handler->verifyShift($shiftId, (int)$auth->user()['id'], $notes);
    
    $_SESSION['flash_success'] = 'Shift verified.';
    header('Location: /admin/shifts');
    exit;
}
