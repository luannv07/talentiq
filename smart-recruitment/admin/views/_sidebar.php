<?php if (!defined('ABSPATH')) exit; ?>
<?php $cp = $_GET['page'] ?? 'smart-recruitment'; ?>
<div class="col-md-2 sr-sidebar d-flex flex-column p-0">
    <div class="sr-sidebar-brand px-3 py-4 d-flex align-items-center">
        <i class="fas fa-brain me-2"></i>TalentIQ
    </div>
    <nav class="sr-sidebar-nav flex-grow-1">
        <a href="<?php echo admin_url('admin.php?page=smart-recruitment'); ?>"
           class="sr-sidebar-link<?php echo $cp === 'smart-recruitment' ? ' active' : ''; ?>">
            <i class="fas fa-chart-pie me-2"></i>Dashboard
        </a>
        <a href="<?php echo admin_url('admin.php?page=sr-jobs'); ?>"
           class="sr-sidebar-link<?php echo $cp === 'sr-jobs' ? ' active' : ''; ?>">
            <i class="fas fa-briefcase me-2"></i>Vị trí tuyển dụng
        </a>
        <a href="<?php echo admin_url('admin.php?page=sr-applications'); ?>"
           class="sr-sidebar-link<?php echo $cp === 'sr-applications' ? ' active' : ''; ?>">
            <i class="fas fa-users me-2"></i>Ứng viên
        </a>
        <a href="<?php echo admin_url('admin.php?page=sr-settings'); ?>"
           class="sr-sidebar-link<?php echo $cp === 'sr-settings' ? ' active' : ''; ?>">
            <i class="fas fa-gear me-2"></i>Cài đặt
        </a>
    </nav>
    <div class="sr-sidebar-footer px-3 py-3">
        <span class="text-white-50 small">v<?php echo SR_VERSION; ?></span>
    </div>
</div>
