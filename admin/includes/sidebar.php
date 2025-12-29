<?php
// Sidebar active link helper
$currentPath = $_SERVER['PHP_SELF'] ?? '';
function is_active($needle, $currentPath)
{
  return (strpos($currentPath, $needle) !== false) ? 'active' : '';
}
?>

<aside class="admin-sidebar" aria-label="القائمة الجانبية">
  <div class="sidebar-brand">
    <div class="sidebar-title">لوحة تحكم الإدارة</div>
    <div class="sidebar-subtitle">التحكم الكامل بالمحتوى</div>
  </div>

  <ul class="sidebar-nav">
    <li><a class="<?= is_active('/index.php', $currentPath) ?>" href="/mutadarrib/index.php" data-icon="home">العودة إلى الصفحة الرئيسية</a></li>
    <li><a class="<?= is_active('/admin/dashboard.php', $currentPath) ?>" href="/mutadarrib/admin/dashboard.php" data-icon="dashboard">الرئيسية</a></li>

    <li class="sidebar-section">إدارة</li>
    <li><a class="<?= is_active('/admin/users/', $currentPath) ?>" href="/mutadarrib/admin/users/users.php" data-icon="users">إدارة المستخدمين</a></li>
    <li><a class="<?= is_active('/admin/lawyers/lawyers.php', $currentPath) ?>" href="/mutadarrib/admin/lawyers/lawyers.php" data-icon="lawyer">إدارة المحامين</a></li>
    <li><a class="<?= is_active('/admin/lawyers/syndicate_lawyers.php', $currentPath) ?>" href="/mutadarrib/admin/lawyers/syndicate_lawyers.php" data-icon="registry">سجل المزاولين</a></li>
    <li><a class="<?= is_active('/admin/trainees/', $currentPath) ?>" href="/mutadarrib/admin/trainees/trainees.php" data-icon="trainee">إدارة المتدربين</a></li>

    <li class="sidebar-section">طلبات</li>
    <li><a class="<?= is_active('/admin/training_applications.php', $currentPath) ?>" href="/mutadarrib/admin/training_applications.php" data-icon="training">طلبات التدريب</a></li>
    <li><a class="<?= is_active('/admin/exams.php', $currentPath) ?>" href="/mutadarrib/admin/exams.php" data-icon="exam">طلبات الامتحان</a></li>
    <li><a class="<?= is_active('/admin/membership/', $currentPath) ?>" href="/mutadarrib/admin/membership/requests.php" data-icon="membership">طلبات الانتساب</a></li>

    <li class="sidebar-section">محتوى</li>
    <li><a class="<?= is_active('/admin/documents/', $currentPath) ?>" href="/mutadarrib/admin/documents/documents.php" data-icon="docs">وثائق المستخدمين</a></li>
    <li><a class="<?= is_active('/admin/contact/', $currentPath) ?>" href="/mutadarrib/admin/contact/messages.php" data-icon="messages">رسائل تواصل معنا</a></li>

    <li class="sidebar-section">نظام</li>
    <li><a class="<?= is_active('/admin/settings.php', $currentPath) ?>" href="/mutadarrib/admin/settings.php" data-icon="settings">الإعدادات</a></li>
    <li><a href="/mutadarrib/auth/logout.php" data-icon="logout">تسجيل الخروج</a></li>
  </ul>
</aside>