<div class="sidebar">
    <h2><?= t('Actions') ?></h2>
    <ul>
        <li>
            <?= $this->url->link(t('About'), 'config', 'index') ?>
        </li>
        <li>
            <?= $this->url->link(t('Application settings'), 'config', 'application') ?>
        </li>
        <li>
            <?= $this->url->link(t('Project settings'), 'config', 'project') ?>
        </li>
        <li>
            <?= $this->url->link(t('Board settings'), 'config', 'board') ?>
        </li>
        <li>
            <?= $this->url->link(t('Calendar settings'), 'config', 'calendar') ?>
        </li>
    </ul>
    <div class="sidebar-collapse"><a href="#" title="<?= t('Hide sidebar') ?>"><i class="fa fa-chevron-left"></i></a></div>
    <div class="sidebar-expand" style="display: none"><a href="#" title="<?= t('Expand sidebar') ?>"><i class="fa fa-chevron-right"></i></a></div>
</div>