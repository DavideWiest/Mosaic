<?php
/* Smarty version 4.4.1, created on 2024-03-17 19:52:42
  from 'C:\xampp\htdocs\Mosaic\src\templates\editFragments\link.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.4.1',
  'unifunc' => 'content_65f73bfacc4111_11881758',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '392a2cdbf85700e911d94007cd368ccbfda1f2fe' => 
    array (
      0 => 'C:\\xampp\\htdocs\\Mosaic\\src\\templates\\editFragments\\link.tpl',
      1 => 1710679816,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_65f73bfacc4111_11881758 (Smarty_Internal_Template $_smarty_tpl) {
?>
<div>
    <label for="Title">Title:</label>
    <input type="text" id="fragment-FragmentLink-Title" name="fragment-FragmentLink-Title" required <?php if (((isset($_smarty_tpl->tpl_vars['fragmentContent']->value)))) {
echo GenericRender::InsertValueAttribute($_smarty_tpl->tpl_vars['fragmentContent']->value,'Title');
}?>>
</div>

<div>
    <label for="Link">Link:</label>
    <input type="text" id="fragment-FragmentLink-Link" name="fragment-FragmentLink-Link" required <?php if (((isset($_smarty_tpl->tpl_vars['fragmentContent']->value)))) {
echo GenericRender::InsertValueAttribute($_smarty_tpl->tpl_vars['fragmentContent']->value,'Link');
}?>>
</div><?php }
}
