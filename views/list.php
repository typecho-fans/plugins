 <?php if ($result): ?>
    <table class="pure-table pure-table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th><?php echo _t('应用名称'); ?></th>
                <th><?php echo _t('应用描述'); ?></th>
                <th><?php echo _t('作者'); ?></th>
                <th><?php echo _t('版本'); ?></th>
                <th><?php echo _t('版本要求'); ?></th>
                <th><?php echo _t('安装'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php $index = 0;
            foreach ($result->packages as $plugin):
            $index++; ?>
            <tr class="as-card" data-name="<?php echo $plugin->name; ?>" data-existed="<?php echo $plugin->existed ?>">
                <td><?php echo $index; ?></td>
                <td><?php echo $plugin->name; ?></td>
                <td class="as-description"><?php echo $plugin->versions[0]->description; ?></td>
                <td class="as-author"><?php echo $plugin->versions[0]->author; ?></td>
                <td class="as-versions">
                    <select class="as-version-selector">
                        <?php foreach ($plugin->versions as $version): ?>
                            <option value="<?php echo $version->version; ?>" data-activated="<?php echo $version->activated; ?>" data-author="<?php echo $version->author; ?>" data-require="<?php echo $version->require; ?>" data-description="<?php echo $version->description; ?>"><?php echo $version->version; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td class="as-require" style="white-space:nowrap;"><?php echo $plugin->versions[0]->require; ?></td>
                <td class="as-operations">
                    <?php if ($this->installale):  ?>
                        <?php if ($plugin->existed): ?>
                            <button class="pure-button as-install button-small"><?php echo _t("重新安装"); ?></button>
                        <?php else: ?>
                            <button class="pure-button pure-button-primary as-install button-small"><?php echo _t("立即安装"); ?></button>
                        <?php endif; ?>
                    <?php else: ?>
                        <a class="pure-button button-small" onclick="return confirm('没有写入权限或者运行在云平台中\n点击确认后将进行下载，请手动传到服务器上!');" href="<?php echo $this->server.'archive/'.$plugin->name.'/'.str_replace(' ', '%20', $version->version);?>"><?php echo _t('下载'); ?></a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <div class="message" style="width:20em;text-align: center;margin:0 auto">
        <p><i class="fa fa-frown-o" style="font-size: 5em"></i></p>
        <h3 style="font-size: 2em">没有找到任何插件</h3>
    </div>
<?php endif; ?>
