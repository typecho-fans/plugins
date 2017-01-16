 <tr class="as-card" data-name="<?php echo $plugin->name; ?>" data-existed="<?php echo $plugin->existed ?>">
    <td class="as-name"><?php echo $plugin->name; ?></td>
    <td class="as-description"><?php echo $plugin->versions[0]->description; ?></td>
    <td class="as-versions">
        <select class="as-version-selector">
            <?php foreach ($plugin->versions as $version): ?>
                <option value="<?php echo $version->version; ?>" data-activated="<?php echo $version->activated; ?>" data-author="<?php echo $version->author; ?>" data-require="<?php echo $version->require; ?>" data-description="<?php echo $version->description; ?>"><?php echo $version->version; ?></option>
            <?php endforeach; ?>
        </select>
    </td>
    <td class="as-require" ><?php echo $plugin->versions[0]->require; ?></td>
    <td class="as-author"><?php echo $plugin->versions[0]->author; ?></td>
    <td class="as-operations">
        <?php if ($this->installale):  ?>
            <?php if ($plugin->existed): ?>
                <a class="as-install" href="javascript:;"><?php echo _t("重装"); ?></a>
            <?php else: ?>
                <a class="as-install" href="javascript:;"><?php echo _t("安装"); ?></a>
            <?php endif; ?>
        <?php else: ?>
            <a onclick="return confirm('没有写入权限或者运行在云平台中\n点击确认后将进行下载，请手动传到服务器上!');" href="<?php echo $this->server.'archive/'.$plugin->name.'/'.str_replace(' ', '%20', $version->version);?>"><?php echo _t('下载'); ?></a>
        <?php endif; ?>
    </td>
</tr>