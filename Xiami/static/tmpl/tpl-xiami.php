<?php
   /**
    * XiaMi_Tmpl 虾米音乐同步模板
    * 
    * @package custom 
    */ 
$this->need('header.php');?>
        <!-- WP_XiaMi start V0.0.1 -->
        <div id="wpxm_rapier">
            <div class="wpxm_music-content"></div>
            <div id="wpxm_music-preview"></div>
        </div>
        <div id="wpxmplayer-box">
            <div class="wpxmplayer-prosess"></div>
            <div class="wpxm_music-content">
                <div class="wpxmplayer-info">
                    <span class="wpxmplayer-title"></span>
                    <span class="wpxmplayer-timer"></span>
                </div>
                <div class="wpxmplayer-control">
                    <ul>
                        <li><a id="wpxmplayer-prev" href="javascript:;"></a></li>
                        <li><a id="wpxmplayer-button" href="javascript:;"></a></li>
                        <li><a id="wpxmplayer-next" href="javascript:;"></a></li>
                    </ul>
                </div>
            </div>
        </div>
        <script id="wpxm_tpl_1" type="text/template">
            <ul class="wpxm-ul">
                {@each collects as it, index}
                    {@if it.collect_cover}
                        <li class="wpxm_music" data-id="${it.collect_id}" data-index="${index}">
                            <div class="wpxm_music-image">
                                <img src="{@if it.collect_cover}${it.collect_cover|parseCover}{@/if}" alt="${it.collect_title}">
                                <span class="wpxm_music-mask"></span>
                            </div>
                            <span class="wpxm_music-title">${it.collect_title|parseText}</span>
                            <span class="wpxm_music-author">${it.collect_author}</span>
                        </li>
                    {@/if}
                {@/each}
            </ul>
        </script>
        <script id="wpxm_tpl_2" type="text/template">
            <div class="wpxm_music-player">
                <div class="wpxm_music-songs-info">${collect_title} - ${collect_author}</div>
                <ol class="wpxm_music-song-list" type="1">
                    {@each songs as it, index}
                    <li class="wpxm_music-song" data-songid="${it.song_id}" data-cid="${cid}" data-index="${index}">
                        <span class="wpxm_music-song-icon">${index|indexPlus}</span>
                        <span class="wpxm_music-song-title">${it.song_title|parseText} - ${it.song_author}</span>
                        <span class="wpxm_music-song-length">${it.song_length|parseTime}</span>
                    </li>
                    {@/each}
                </ol>
                <div class="wpxm_music-songs-tip">* 单击播放，空格键暂停播放，← 键 上一首， → 键 下一首。</div>
                <div class="wpxm_music-close"></div>
            </div>
        </script>
        <!-- WP_XiaMi end V0.0.1 -->
<?php $this->need('footer.php'); ?>
