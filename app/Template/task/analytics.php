<div class="page-header">
    <h2><?= t('Analytics') ?></h2>
</div>

<div class="listing">
    <ul>
        <li><?= t('Lead time: ').'<strong>'.$this->dt->duration($lead_time) ?></strong></li>
        <li><?= t('Cycle time: ').'<strong>'.$this->dt->duration($cycle_time) ?></strong></li>
    </ul>
</div>

<h3 id="analytic-task-time-column"><?= t('Time spent into each column') ?></h3>
<div id="chart" data-metrics='<?= json_encode($time_spent_columns) ?>' data-label="<?= t('Time spent') ?>"></div>
<table class="table-stripped">
    <tr>
        <th><?= t('Column') ?></th>
        <th><?= t('Time spent') ?></th>
    </tr>
    <?php foreach ($time_spent_columns as $column): ?>
    <tr>
        <td><?= $this->e($column['title']) ?></td>
        <td><?= $this->dt->duration($column['time_spent']) ?></td>
    </tr>
    <?php endforeach ?>
</table>

<div class="alert alert-info">
    <ul>
        <li><?= t('Thời gian thực hiện là khoảng thời gian giữa việc tạo ra nhiệm vụ và hoàn thành.') ?></li>
        <li><?= t('Chu kỳ là khoảng thời gian giữa ngày bắt đầu và kết thúc.') ?></li>
        <li><?= t('Nếu nhiệm vụ không được đóng, thời gian hiện tại sẽ được tính thay vì thời gian hoàn thành.') ?></li>
    </ul>
</div>

<?= $this->asset->js('assets/js/vendor/d3.v3.min.js') ?>
<?= $this->asset->js('assets/js/vendor/c3.min.js') ?>