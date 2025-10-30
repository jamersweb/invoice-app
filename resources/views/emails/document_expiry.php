<p><?= __('Your document is approaching expiry.') ?></p>
<p><?= __('Document ID:') ?> <?= $document->id ?></p>
<p><?= __('Expiry at:') ?> <?= optional($document->expiry_at)->toDateString() ?></p>

