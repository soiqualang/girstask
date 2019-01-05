<div class="page-header">
    <h2><?= t('Hourly rates') ?></h2>
</div>

<?php if (! empty($rates)): ?>

<table>
    <tr>
        <th><?= t('Hourly rate') ?></th>
        <th><?= t('Currency') ?></th>
        <th><?= t('Effective date') ?></th>
        <th><?= t('Action') ?></th>
    </tr>
    <?php foreach ($rates as $rate): ?>
    <tr>
        <td><?= n($rate['rate']) ?></td>
        <td><?= $rate['currency'] ?></td>
        <td><?= dt('%b %e, %Y', $rate['date_effective']) ?></td>
        <td>
            <?= $this->url->link(t('Remove'), 'hourlyrate', 'confirm', array('user_id' => $user['id'], 'rate_id' => $rate['id'])) ?>
        </td>
    </tr>
    <?php endforeach ?>
</table>

<h3><?= t('Add new rate') ?></h3>
<?php endif ?>

<form method="post" action="<?= $this->url->href('hourlyrate', 'save', array('user_id' => $user['id'])) ?>" autocomplete="off">

    <?= $this->form->hidden('user_id', $values) ?>
    <?= $this->form->csrf() ?>

    <?= $this->form->label(t('Hourly rate'), 'rate') ?>
    <?= $this->form->text('rate', $values, $errors, array('required'), 'form-numeric') ?>

    <?= $this->form->label(t('Currency'), 'currency') ?>
    <?= $this->form->select('currency', $currencies_list, $values, $errors, array('required')) ?>

    <?= $this->form->label(t('Effective date'), 'date_effective') ?>
    <?= $this->form->text('date_effective', $values, $errors, array('required'), 'form-date') ?>

    <div class="form-actions">
        <input type="submit" value="<?= t('Save') ?>" class="btn btn-blue"/>
    </div>
</form>
