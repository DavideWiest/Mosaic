<?php
/* Smarty version 4.4.1, created on 2024-03-16 22:50:48
  from 'C:\xampp\htdocs\Mosaic\src\templates\editFragments\credentials.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.4.1',
  'unifunc' => 'content_65f6143828a5c1_93202013',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '862809e4815993c511255494d1e9c129bad93960' => 
    array (
      0 => 'C:\\xampp\\htdocs\\Mosaic\\src\\templates\\editFragments\\credentials.tpl',
      1 => 1710625846,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_65f6143828a5c1_93202013 (Smarty_Internal_Template $_smarty_tpl) {
?>
<div>
    <label for="Username">Username:</label>
    <input type="text" id="Username" name="Username" required <?php echo GenericRender::InsertValueAttribute($_smarty_tpl->tpl_vars['extraFragmentContent']->value,'Username');?>
>
</div>
<div>
    <label for="ShowPersonalData">ShowPersonalData:</label>
    <input type="checkbox" id="fragment-FragmentCredentials-ShowPersonalData" name="fragment-FragmentCredentials-ShowPersonalData" <?php echo GenericRender::InsertValueAttribute($_smarty_tpl->tpl_vars['fragmentContent']->value,'ShowPersonalData');?>
>
</div>
<?php }
}
