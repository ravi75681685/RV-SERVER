<?php
$storage_file = 'updates.json';

// Initialize file if not exists
if (!file_exists($storage_file)) {
    file_put_contents($storage_file, json_encode(new stdClass()));
}

// Handle delete request
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $package_to_delete = $_GET['delete'];
    $updates = json_decode(file_get_contents($storage_file), true) ?: [];
    
    if (isset($updates[$package_to_delete])) {
        unset($updates[$package_to_delete]);
        file_put_contents($storage_file, json_encode($updates, JSON_PRETTY_PRINT));
        echo "<script>alert('Update for $package_to_delete deleted successfully!');</script>";
    }
}

// Cleanup old updates (24 hours)
function cleanupOldUpdates() {
    global $storage_file;
    $updates = json_decode(file_get_contents($storage_file), true) ?: [];
    $now = time();
    $changed = false;
    
    foreach ($updates as $package => $data) {
        if (($now - $data['timestamp']) > 86400) { // 24 hours in seconds
            unset($updates[$package]);
            $changed = true;
        }
    }
    
    if ($changed) {
        file_put_contents($storage_file, json_encode($updates, JSON_PRETTY_PRINT));
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $note = $_POST['note'] ?? '';
    $url = $_POST['url'] ?? '';
    $force_update = isset($_POST['force_update']) ? true : false;
    $version_code = $_POST['version_code'] ?? '';
    $package_name = $_POST['package_name'] ?? '';

    // Validate inputs
    if (empty($title) || empty($note) || empty($package_name) || empty($version_code)) {
        die("Error: Title, Note, Package Name aur Version Code zaruri hain");
    }

    // Load existing data
    $existing_data = json_decode(file_get_contents($storage_file), true) ?: [];

    // Create/Update entry for this package
    $existing_data[$package_name] = [
        'title' => $title,
        'note' => $note,
        'url' => $url,
        'force_update' => $force_update,
        'version_code' => $version_code,
        'timestamp' => time()
    ];

    // Save to JSON file
    file_put_contents($storage_file, json_encode($existing_data, JSON_PRETTY_PRINT));

    echo "<script>alert('Update successfully save ho gaya!'); window.location.href='?package_preview=$package_name';</script>";
}

// API endpoint for app to check updates
if (isset($_GET['action']) && $_GET['action'] == 'check_update') {
    header('Content-Type: application/json');
    
    $app_package = $_GET['package_name'] ?? '';
    $app_version = $_GET['version_code'] ?? '';
    
    if (empty($app_package)) {
        die(json_encode(['error' => 'Package name zaruri hai']));
    }

    cleanupOldUpdates(); // Cleanup before checking
    $updates = json_decode(file_get_contents($storage_file), true) ?: [];
    $response = ['has_update' => false];

    if (isset($updates[$app_package])) {
        $update = $updates[$app_package];
        
        // Check version code
        if ($app_version != $update['version_code']) {
            $response = [
                'has_update' => true,
                'title' => $update['title'],
                'note' => $update['note'],
                'url' => $update['url'],
                'force_update' => $update['force_update']
            ];
        }
    }
    
    echo json_encode($response);
    exit;
}

// Get current update for admin panel
$current_update = [];
$package_preview = $_GET['package_preview'] ?? '';
if (!empty($package_preview)) {
    cleanupOldUpdates(); // Cleanup before displaying
    $updates = json_decode(file_get_contents($storage_file), true) ?: [];
    if (isset($updates[$package_preview])) {
        $current_update = $updates[$package_preview];
        $current_update['package_name'] = $package_preview;
    }
}

// Get all updates for display
$all_updates = json_decode(file_get_contents($storage_file), true) ?: [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Update System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        function toggleUpdates() {
            const updatesSection = document.getElementById('updates-section');
            const toggleButton = document.getElementById('toggle-updates');
            if (updatesSection.classList.contains('hidden')) {
                updatesSection.classList.remove('hidden');
                toggleButton.textContent = 'Hide All Updates';
            } else {
                updatesSection.classList.add('hidden');
                toggleButton.textContent = 'Show All Updates';
            }
        }

        function toggleCurrentUpdate() {
            const currentUpdateSection = document.getElementById('current-update-section');
            const toggleButton = document.getElementById('toggle-current-update');
            if (currentUpdateSection.classList.contains('hidden')) {
                currentUpdateSection.classList.remove('hidden');
                toggleButton.textContent = 'Hide Current Update';
            } else {
                currentUpdateSection.classList.add('hidden');
                toggleButton.textContent = 'Show Current Update';
            }
        }
    </script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-4xl p-6 bg-white rounded-lg shadow-lg">
        <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">Enhanced Update System</h1>
        
        <form method="POST" class="space-y-4">
            <div>
                <label for="package_name" class="block text-sm font-medium text-gray-700">Package Name:</label>
                <input type="text" id="package_name" name="package_name" required
                       value="<?= htmlspecialchars($package_preview) ?>"
                       class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            
            <div>
                <label for="version_code" class="block text-sm font-medium text-gray-700">Target Version Code:</label>
                <input type="text" id="version_code" name="version_code" required
                       value="<?= htmlspecialchars($current_update['version_code'] ?? '') ?>"
                       class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700">Title:</label>
                <input type="text" id="title" name="title" required
                       value="<?= htmlspecialchars($current_update['title'] ?? '') ?>"
                       class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            
            <div>
                <label for="note" class="block text-sm font-medium text-gray-700">Update Note:</label>
                <textarea id="note" name="note" required
                          class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 h-32"><?= htmlspecialchars($current_update['note'] ?? '') ?></textarea>
            </div>
            
            <div>
                <label for="url" class="block text-sm font-medium text-gray-700">Download URL:</label>
                <input type="url" id="url" name="url"
                       value="<?= htmlspecialchars($current_update['url'] ?? '') ?>"
                       class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            
            <div class="flex items-center">
                <input type="checkbox" id="force_update" name="force_update"
                       <?= ($current_update['force_update'] ?? false) ? 'checked' : '' ?>
                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                <label for="force_update" class="ml-2 text-sm text-gray-700">Force Update (User cannot cancel)</label>
            </div>
            
            <button type="submit" class="w-full md:w-auto bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition">Save Update</button>
        </form>
        
        <div class="mt-6">
            <button id="toggle-current-update" onclick="toggleCurrentUpdate()" class="w-full md:w-auto bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600 transition mb-4">
                <?= !empty($current_update) ? 'Show Current Update' : 'No Current Update' ?>
            </button>
            <div id="current-update-section" class="hidden p-4 bg-gray-50 rounded-lg">
                <?php if (!empty($current_update)): ?>
                    <h3 class="text-lg font-semibold text-gray-800">Current Update for <?= htmlspecialchars($package_preview) ?></h3>
                    <p><strong>Version:</strong> <?= htmlspecialchars($current_update['version_code']) ?></p>
                    <p><strong>Title:</strong> <?= htmlspecialchars($current_update['title']) ?></p>
                    <p><strong>Note:</strong> <?= nl2br(htmlspecialchars($current_update['note'])) ?></p>
                    <p><strong>URL:</strong> <?= htmlspecialchars($current_update['url']) ?></p>
                    <p><strong>Force Update:</strong> <?= $current_update['force_update'] ? 'Yes' : 'No' ?></p>
                    <p><small>Last updated: <?= date('Y-m-d H:i:s', $current_update['timestamp']) ?></small></p>
                <?php else: ?>
                    <p class="text-gray-600">No current update selected. Please select a package or create a new update.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mt-6">
            <button id="toggle-updates" onclick="toggleUpdates()" class="w-full md:w-auto bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 transition">Show All Updates</button>
            <div id="updates-section" class="hidden mt-4 space-y-4">
                <h2 class="text-xl font-semibold text-gray-800">All Active Updates</h2>
                <?php if (empty($all_updates)): ?>
                    <p class="text-gray-600">No updates currently active</p>
                <?php else: ?>
                    <?php foreach ($all_updates as $package => $update): ?>
                        <div class="p-4 bg-white rounded-lg shadow">
                            <div class="flex justify-between items-center mb-2">
                                <h3 class="text-lg font-semibold"><?= htmlspecialchars($package) ?> (v<?= htmlspecialchars($update['version_code']) ?>)</h3>
                                <div class="space-x-2">
                                    <a href="?package_preview=<?= urlencode($package) ?>" class="bg-blue-500 text-white px-3 py-1 rounded-md hover:bg-blue-600">View</a>
                                    <a href="?delete=<?= urlencode($package) ?>" class="bg-red-500 text-white px-3 py-1 rounded-md hover:bg-red-600" 
                                       onclick="return confirm('Are you sure you want to delete this update?')">Delete</a>
                                </div>
                            </div>
                            <p><strong>Title:</strong> <?= htmlspecialchars($update['title']) ?></p>
                            <p><strong>Status:</strong> <?= (time() - $update['timestamp'] > 86400) ? 'Expired' : 'Active' ?></p>
                            <p><small>Created: <?= date('Y-m-d H:i:s', $update['timestamp']) ?></small></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>