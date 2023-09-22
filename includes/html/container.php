<?php
if (!defined("ABSPATH")) {
    exit();
}
?>
<div id="wun-container" class="wun-container" style="flex-basis: 100%;">
    <div class="wun-notifications" style="display: none;">
        <div class="wun-head">

            <div class="wun-head-bell-wrap-m">
                <div class="wun-head-bell-wrap">
                    <svg class="wun-head-bell" viewBox="0 0 30 30" xmlns="http://www.w3.org/2000/svg"><path d="M14.25 26.5c0-0.141-0.109-0.25-0.25-0.25-1.234 0-2.25-1.016-2.25-2.25 0-0.141-0.109-0.25-0.25-0.25s-0.25 0.109-0.25 0.25c0 1.516 1.234 2.75 2.75 2.75 0.141 0 0.25-0.109 0.25-0.25zM3.844 22h20.312c-2.797-3.156-4.156-7.438-4.156-13 0-2.016-1.906-5-6-5s-6 2.984-6 5c0 5.563-1.359 9.844-4.156 13zM27 22c0 1.094-0.906 2-2 2h-7c0 2.203-1.797 4-4 4s-4-1.797-4-4h-7c-1.094 0-2-0.906-2-2 2.312-1.953 5-5.453 5-13 0-3 2.484-6.281 6.625-6.891-0.078-0.187-0.125-0.391-0.125-0.609 0-0.828 0.672-1.5 1.5-1.5s1.5 0.672 1.5 1.5c0 0.219-0.047 0.422-0.125 0.609 4.141 0.609 6.625 3.891 6.625 6.891 0 7.547 2.688 11.047 5 13z"></path></svg>
                </div><span><?php echo $this->options->data["ntfContainerTitle"]; ?></span>
            </div>

            <div class="wun-loader">
                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                     width="36px" height="30px" viewBox="0 0 24 30" style="enable-background:new 0 0 50 50;" xml:space="preserve">
                    <rect x="0" y="10" width="4" height="10" fill="#333" opacity="0.2">
                        <animate attributeName="opacity" attributeType="XML" values="0.2; 1; .2" begin="0s" dur="0.6s" repeatCount="indefinite" />
                        <animate attributeName="height" attributeType="XML" values="10; 20; 10" begin="0s" dur="0.6s" repeatCount="indefinite" />
                        <animate attributeName="y" attributeType="XML" values="10; 5; 10" begin="0s" dur="0.6s" repeatCount="indefinite" />                                
                    </rect>
                    <rect x="8" y="10" width="4" height="10" fill="#333"  opacity="0.2">
                        <animate attributeName="opacity" attributeType="XML" values="0.2; 1; .2" begin="0.15s" dur="0.6s" repeatCount="indefinite" />
                        <animate attributeName="height" attributeType="XML" values="10; 20; 10" begin="0.15s" dur="0.6s" repeatCount="indefinite" />
                        <animate attributeName="y" attributeType="XML" values="10; 5; 10" begin="0.15s" dur="0.6s" repeatCount="indefinite" />                                
                    </rect>
                    <rect x="16" y="10" width="4" height="10" fill="#333"  opacity="0.2">
                        <animate attributeName="opacity" attributeType="XML" values="0.2; 1; .2" begin="0.3s" dur="0.6s" repeatCount="indefinite" />
                        <animate attributeName="height" attributeType="XML" values="10; 20; 10" begin="0.3s" dur="0.6s" repeatCount="indefinite" />
                        <animate attributeName="y" attributeType="XML" values="10; 5; 10" begin="0.3s" dur="0.6s" repeatCount="indefinite" />                                
                    </rect>
                    <rect x="24" y="10" width="4" height="10" fill="#333"  opacity="0.2">
                        <animate attributeName="opacity" attributeType="XML" values="0.2; 1; .2" begin="0.45s" dur="0.6s" repeatCount="indefinite" />
                        <animate attributeName="height" attributeType="XML" values="10; 20; 10" begin="0.45s" dur="0.6s" repeatCount="indefinite" />
                        <animate attributeName="y" attributeType="XML" values="10; 5; 10" begin="0.45s" dur="0.6s" repeatCount="indefinite" />                                
                    </rect>
                </svg>
            </div>

            <div style="clear: both;"></div>
        </div>
        <div class="wun-content">
            <dl class="wun-list"></dl>
        </div>
        <div class="wun-actions" style="display: none;">
            <span class="wun-action wun-action-load-more"><?php echo $this->options->data["ntfLoadMore"]; ?>
                <?php if ($this->options->data["showCountOfNotLoaded"]) { ?>
                    &nbsp;<span class="wun-items-left wun-hidden"></span>
                <?php } ?>
            </span>
            <span class="wun-action wun-action-delete-all" data-nonce=""><?php echo $this->options->data["ntfDeleteAll"]; ?></span>
        </div>
    </div>
</div>