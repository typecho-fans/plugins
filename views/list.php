 <?php if ($result): ?>
    <div class="pure-g typecho-list">
        <?php foreach ($result->packages as $plugin): ?>
        <div class="pure-u-1-4 as-card" data-name="<?php echo $plugin->name; ?>" data-existed="<?php echo $plugin->existed ?>">
            <h3><?php echo $plugin->name; ?></h3>
            <p class="as-description" title="<?php echo $plugin->versions[0]->description; ?>">
                <?php echo $plugin->versions[0]->description; ?>
            </p>
            <p class="as-author">
                <?php echo _t('作者'); ?>:
                <cite><?php echo $plugin->versions[0]->author; ?></cite>
            </p>
            <p class="as-versions">
                <?php echo _t('版本'); ?>:
                <select class="as-version-selector">
                    <?php foreach ($plugin->versions as $version): ?>
                        <option value="<?php echo $version->version; ?>" data-activated="<?php echo $version->activated; ?>" data-author="<?php echo $version->author; ?>" data-require="<?php echo $version->require; ?>" data-description="<?php echo $version->description; ?>"><?php echo $version->version; ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p class="as-require">
                <?php echo _t('版本要求'); ?>:
                <cite><?php echo $plugin->versions[0]->require; ?></cite>
            </p>
            <p class="as-operations">
                <button class="btn-s as-install"><?php echo _t('安装'); ?></button>
                <span class="as-status" style="">
                    <?php if ($plugin->existed): ?>
                        <i class="fa fa-check-circle as-activated as-existed active" title="<?php echo _t('已安装'); ?>"></i>
                    <?php else: ?>
                        <i class="fa fa-check-circle as-activated" title="<?php echo _t('未安装'); ?>"></i>
                    <?php endif; ?>
                </span>
            </p>
        </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="message" style="width:20em;text-align: center;margin:0 auto">
        <p><i class="fa fa-frown-o" style="font-size: 5em"></i></p>
        <h3 style="font-size: 2em">没有找到任何插件</h3>
    </div>
<?php endif; ?>