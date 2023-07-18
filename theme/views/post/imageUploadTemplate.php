<?php
if (c('Zotero.ImageUploads')) { ?>
    <div id='forum-images-container'></div>
    <template id='forum-images-template'>
        <style>
            div.buttons {
                text-align: right;
            }
            div#image-drop-zone {
                margin-top: 1rem;
                border: 2px dashed #888;
                min-height: 5rem;
                padding: 1rem;
            }
            div#image-drop-zone.hidden {
                display:none;
            }
            div#image-drop-zone.highlight {
                background-color: #99f;
            }
            div#image-drop-zone #fileElem {
                display:none;
            }
            div#image-upload-preview img.preview-image {
                max-width: 300px;
                margin: 5px;
            }

            button.Button {
                font-weight: normal;
                line-height: 1;
                font-size: 12px;
                padding: 4px 6px;
                border: 1px solid #999;
                background: #f8f8f8;
                border-radius: 2px;
                background-image: -webkit-gradient( linear, left bottom, left top, color-stop(0, #CCCCCC), color-stop(1, #FAFAFA) )
            }
        </style>
        <div>
            <div class='buttons'>
                <button type='button' class='Button' id='add-image-button'>Add Image</button>
            </div>
            <div id="image-upload-previewUrls"></div>
            <div id="image-drop-zone" class="hidden">
                <form class="image-upload-form">
                    <p>Upload image file with the file dialog or by dragging and dropping images onto the dashed region</p>
                    <input type="file" id="fileElem" multiple accept="image/*" >
                    <button class="Button" id="file-picker-button" for="fileElem">Select a file</button>
                </form>
            </div>
            <div id="image-upload-preview">
            </div>
        </div>
    </template>
    <template id="url-preview-template">
        <style>
            a {
                
            }
            img {
                max-width: 200px;
            }
            .hidden {
                display: none;
            }
            div.image-container-div {
                clear: both;
            }
        </style>
        <div class='url-preview-container'>
            <a href="" target="_blank">Uploaded Image</a>(<a href="#" class='toggle-preview button'>Show</a>) <a href="#" class='delete-image button'>Delete</a>
            <div class='image-container-div'><img class='hidden' /></div>
        </div>
    </template>
<? } ?>
