<?php
/**
 * Alert Flash Notification View
 * TechPulse IMS
 */
$flash = get_flash();
if ($flash):
    $alert_class = 'alert-info';
    $icon = 'ℹ️';
    
    if ($flash['type'] === 'success') {
        $alert_class = 'alert-success';
        $icon = '✅';
    } elseif ($flash['type'] === 'danger') {
        $alert_class = 'alert-danger';
        $icon = '❌';
    } elseif ($flash['type'] === 'warning') {
        $alert_class = 'alert-warning';
        $icon = '⚠️';
    }
?>
    <div class="alert <?= $alert_class ?>">
        <span style="font-size: 1.2rem;"><?= $icon ?></span>
        <div><?= e($flash['message']) ?></div>
    </div>
<?php endif; ?>
