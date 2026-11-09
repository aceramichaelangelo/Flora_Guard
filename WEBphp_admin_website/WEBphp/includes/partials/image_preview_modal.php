<?php

declare(strict_types=1);

?>

<div id="scanImageModal" class="modal-overlay scan-image-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="scanImageModalTitle">

    <div class="scan-image-modal__panel" role="document">

        <button type="button" class="scan-image-modal__close" id="scanImageModalClose" aria-label="Close preview">&times;</button>

        <h3 id="scanImageModalTitle" class="scan-image-modal__title"></h3>

        <div class="scan-image-modal__frame">

            <img id="scanImageModalImg" src="" alt="" class="scan-image-modal__img">

        </div>

        <div class="scan-image-modal__actions">

            <a id="scanImageModalOpen" href="#" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm">Open full size</a>

            <button type="button" class="btn btn-primary btn-sm" id="scanImageModalDone">Close</button>

        </div>

    </div>

</div>

