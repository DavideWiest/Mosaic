<?php
/* Smarty version 4.4.1, created on 2024-03-15 14:19:29
  from 'C:\xampp\htdocs\Mosaic\src\templates\site_components\planOverview.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.4.1',
  'unifunc' => 'content_65f44ae1adac53_90612043',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '8488652fb7447ca489588cd7c8a721b4f5c30a90' => 
    array (
      0 => 'C:\\xampp\\htdocs\\Mosaic\\src\\templates\\site_components\\planOverview.tpl',
      1 => 1710503131,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:site_components/plan.tpl' => 1,
  ),
),false)) {
function content_65f44ae1adac53_90612043 (Smarty_Internal_Template $_smarty_tpl) {
?><div class="md:grid md:grid-cols-3">
    <?php
$_smarty_tpl->tpl_vars['i'] = new Smarty_Variable(null, $_smarty_tpl->isRenderingCache);$_smarty_tpl->tpl_vars['i']->step = 1;$_smarty_tpl->tpl_vars['i']->total = (int) ceil(($_smarty_tpl->tpl_vars['i']->step > 0 ? count($_smarty_tpl->tpl_vars['plans']->value)-1+1 - (0) : 0-(count($_smarty_tpl->tpl_vars['plans']->value)-1)+1)/abs($_smarty_tpl->tpl_vars['i']->step));
if ($_smarty_tpl->tpl_vars['i']->total > 0) {
for ($_smarty_tpl->tpl_vars['i']->value = 0, $_smarty_tpl->tpl_vars['i']->iteration = 1;$_smarty_tpl->tpl_vars['i']->iteration <= $_smarty_tpl->tpl_vars['i']->total;$_smarty_tpl->tpl_vars['i']->value += $_smarty_tpl->tpl_vars['i']->step, $_smarty_tpl->tpl_vars['i']->iteration++) {
$_smarty_tpl->tpl_vars['i']->first = $_smarty_tpl->tpl_vars['i']->iteration === 1;$_smarty_tpl->tpl_vars['i']->last = $_smarty_tpl->tpl_vars['i']->iteration === $_smarty_tpl->tpl_vars['i']->total;?>
        <div class="p-1 m-1 md:m-3">
            <?php $_smarty_tpl->_subTemplateRender("file:site_components/plan.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array('plan'=>$_smarty_tpl->tpl_vars['plans']->value[$_smarty_tpl->tpl_vars['i']->value],'planperm'=>$_smarty_tpl->tpl_vars['planperms']->value[$_smarty_tpl->tpl_vars['i']->value],'planColumnTypeData'=>$_smarty_tpl->tpl_vars['planColumnTypeData']->value,'planpermColumnTypeData'=>$_smarty_tpl->tpl_vars['planpermColumnTypeData']->value), 0, true);
?>
        </div>
    <?php }
}
?>
</div><?php }
}
