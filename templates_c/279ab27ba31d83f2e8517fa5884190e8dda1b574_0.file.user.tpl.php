<?php
/* Smarty version 4.4.1, created on 2024-03-14 23:33:12
  from 'C:\xampp\htdocs\Mosaic\src\templates\user.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.4.1',
  'unifunc' => 'content_65f37b28d5e7a0_20121739',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '279ab27ba31d83f2e8517fa5884190e8dda1b574' => 
    array (
      0 => 'C:\\xampp\\htdocs\\Mosaic\\src\\templates\\user.tpl',
      1 => 1710455592,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:user/userProfile.tpl' => 1,
    'file:user/subsiteList.tpl' => 1,
  ),
),false)) {
function content_65f37b28d5e7a0_20121739 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, true);
?>

<?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_136064244065f37b28d4d871_16138289', 'content');
$_smarty_tpl->inheritance->endChild($_smarty_tpl, 'base/base.tpl');
}
/* {block 'content'} */
class Block_136064244065f37b28d4d871_16138289 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'content' => 
  array (
    0 => 'Block_136064244065f37b28d4d871_16138289',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
?>

<?php if (!(isset($_smarty_tpl->tpl_vars['isOwner']->value))) {?>
    <?php $_smarty_tpl->_assignInScope('isOwner', false);
}?>


<?php $_smarty_tpl->_subTemplateRender('file:user/userProfile.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>

<?php $_smarty_tpl->_subTemplateRender('file:user/subsiteList.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>

<?php if ($_smarty_tpl->tpl_vars['isOwner']->value) {?>
    <a href="<?php echo BusinessConstants::$UNIVERSAL_ROUTE_PREFIX;?>
/logout">
        Logout
    </a>
<?php }?>

<?php
}
}
/* {/block 'content'} */
}
