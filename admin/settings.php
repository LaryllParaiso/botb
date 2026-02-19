<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/AdminController.php';

$controller = new AdminController(Database::getInstance());

$pageTitle = 'Settings — BotB Tabulator';
$pageScript = '/BOB_SYSTEM/assets/js/admin.js';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar_admin.php';
?>

        <div class="container-fluid p-4">
            <h4 class="mb-4"><i class="bi bi-gear"></i> Settings</h4>

            <!-- Settings Tabs -->
            <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="event-tab" data-bs-toggle="tab" data-bs-target="#event-pane" type="button" role="tab">
                        <i class="bi bi-calendar-event"></i> Event Configuration
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="logos-tab" data-bs-toggle="tab" data-bs-target="#logos-pane" type="button" role="tab">
                        <i class="bi bi-image"></i> Logo Configuration
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="credentials-tab" data-bs-toggle="tab" data-bs-target="#credentials-pane" type="button" role="tab">
                        <i class="bi bi-shield-lock"></i> Admin Credentials
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <!-- Event Configuration Tab -->
                <div class="tab-pane fade show active" id="event-pane" role="tabpanel">
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div class="card shadow-sm">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="bi bi-calendar-event"></i> Event Configuration</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="settingEventTitle" class="form-label fw-semibold">Event Title</label>
                                        <input type="text" class="form-control" id="settingEventTitle" placeholder="e.g. Battle of the Bands - NEUST 2026">
                                        <small class="form-text text-muted">Appears as the main heading on rankings printout/export.</small>
                                    </div>
                                    <div class="mb-3">
                                        <label for="settingEventSubtitle" class="form-label fw-semibold">Event Subtitle</label>
                                        <input type="text" class="form-control" id="settingEventSubtitle" placeholder="e.g. 118th Founding Anniversary / 28th Charter Day">
                                        <small class="form-text text-muted">Appears below the title on rankings printout/export.</small>
                                    </div>
                                    <button class="btn btn-primary" onclick="saveSettings()">
                                        <i class="bi bi-check-circle"></i> Save Event Settings
                                    </button>

                                    <hr class="my-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="fw-semibold mb-0">Signatories (Rankings Footer)</h6>
                                        <button class="btn btn-sm btn-outline-primary" onclick="addSignatoryRow()">
                                            <i class="bi bi-plus-circle"></i> Add Signatory
                                        </button>
                                    </div>
                                    <div id="signatoriesContainer">
                                        <!-- Signatory rows populated by JS -->
                                    </div>
                                    <button class="btn btn-primary mt-2" onclick="saveSettings()">
                                        <i class="bi bi-check-circle"></i> Save Signatories
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Logo Configuration Tab -->
                <div class="tab-pane fade" id="logos-pane" role="tabpanel">
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div class="card shadow-sm">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="bi bi-image"></i> Logo Configuration</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Header Left Logo (e.g. NEUST Seal)</label>
                                        <div class="d-flex align-items-center gap-3">
                                            <img id="logoLeftPreview" src="/BOB_SYSTEM/assets/image 23.png" alt="Left Logo" style="width:80px;height:80px;object-fit:contain;border:1px solid #ddd;border-radius:6px;padding:4px;">
                                            <div class="flex-grow-1">
                                                <input type="file" class="form-control" id="logoLeftFile" accept="image/*" onchange="uploadLogo('logo_left', this)">
                                                <small class="form-text text-muted">Max 5MB. PNG, JPG, GIF, WebP, SVG.</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Header Right Logo (e.g. BotB Logo)</label>
                                        <div class="d-flex align-items-center gap-3">
                                            <img id="logoRightPreview" src="/BOB_SYSTEM/assets/Mask group.png" alt="Right Logo" style="width:80px;height:80px;object-fit:contain;border:1px solid #ddd;border-radius:6px;padding:4px;">
                                            <div class="flex-grow-1">
                                                <input type="file" class="form-control" id="logoRightFile" accept="image/*" onchange="uploadLogo('logo_right', this)">
                                                <small class="form-text text-muted">Max 5MB. PNG, JPG, GIF, WebP, SVG.</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Watermark / Footer Logo</label>
                                        <div class="d-flex align-items-center gap-3">
                                            <img id="logoWatermarkPreview" src="/BOB_SYSTEM/assets/Mask group (1).png" alt="Watermark Logo" style="width:80px;height:80px;object-fit:contain;border:1px solid #ddd;border-radius:6px;padding:4px;">
                                            <div class="flex-grow-1">
                                                <input type="file" class="form-control" id="logoWatermarkFile" accept="image/*" onchange="uploadLogo('logo_watermark', this)">
                                                <small class="form-text text-muted">Max 5MB. Appears as watermark on print.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Admin Credentials Tab -->
                <div class="tab-pane fade" id="credentials-pane" role="tabpanel">
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div class="card shadow-sm">
                                <div class="card-header bg-dark text-white">
                                    <h5 class="mb-0"><i class="bi bi-shield-lock"></i> Admin Credentials</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="adminName" class="form-label fw-semibold">Name</label>
                                        <input type="text" class="form-control" id="adminName" value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="adminEmail" class="form-label fw-semibold">Email</label>
                                        <input type="email" class="form-control" id="adminEmail" value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="adminNewPassword" class="form-label fw-semibold">New Password</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="adminNewPassword" placeholder="Leave blank to keep current">
                                            <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('adminNewPassword', this)" tabindex="-1">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                        <small class="form-text text-muted">Minimum 6 characters.</small>
                                    </div>
                                    <hr>
                                    <div class="mb-3">
                                        <label for="adminCurrentPassword" class="form-label fw-semibold text-danger">Current Password <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="adminCurrentPassword" placeholder="Required to save changes">
                                            <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('adminCurrentPassword', this)" tabindex="-1">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                        <small class="form-text text-muted">Enter your current password to confirm changes.</small>
                                    </div>
                                    <button class="btn btn-dark" onclick="saveAdminCredentials()">
                                        <i class="bi bi-save"></i> Update Credentials
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- end mainContent flex wrapper -->
</div><!-- end d-flex -->

<!-- Loading overlay -->
<div class="loading-overlay" id="loadingOverlay" style="display: none;">
    <div class="spinner-border text-light" role="status" style="width: 3rem; height: 3rem;">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
