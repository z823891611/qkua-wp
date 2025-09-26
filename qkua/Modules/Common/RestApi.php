<?php namespace Qk\Modules\Common;

use Qk\Modules\Templates\Modules\Posts;
use Qk\Modules\Common\Post;
use Qk\Modules\Common\User;
use Qk\Modules\Common\Signin;
use Qk\Modules\Common\FileUpload;
use Qk\Modules\Common\Comment;
use Qk\Modules\Common\Pay;
use Qk\Modules\Common\Danmaku;
use Qk\Modules\Common\Oauth;
use Qk\Modules\Common\Invite;
use Qk\Modules\Common\Report;

class RestApi{

    public function init(){
        add_action( 'rest_api_init', array($this,'qk_rest_regeister'));
    }
    
    public function qk_rest_regeister(){

        /************************************ 登录与注册开始 ************************************************/
        //用户注册
        register_rest_route('qk/v1','/regeister',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'regeister'),
            'permission_callback' => '__return_true'
        ));
        
        //用户登出
        register_rest_route('qk/v1','/loginOut',array(
            'methods'=>'get',
            'callback'=>array('Qk\Modules\Common\Login','login_out'),
            'permission_callback' => '__return_true'
        ));
        
        //发送短信或者邮箱验证码
        register_rest_route('qk/v1','/sendCode',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'sendCode'),
            'permission_callback' => '__return_true'
        ));
        
        //获取允许的社交登录
        register_rest_route('qk/v1','/getEnabledOauths',array(
            'methods'=>'get',
            'callback'=>array(__CLASS__,'getEnabledOauths'),
            'permission_callback' => '__return_true'
        ));
        
        //获取登录设置
        register_rest_route('qk/v1','/getLoginSettings',array(
            'methods'=>'get',
            'callback'=>array('Qk\Modules\Common\Login','get_login_settings'),
            'permission_callback' => '__return_true'
        ));
        
        //社交登录
        register_rest_route('qk/v1','/socialLogin',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'socialLogin'),
            'permission_callback' => '__return_true'
        ));
        
        //检查邀请码
        register_rest_route('qk/v1','/checkInviteCode',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'checkInviteCode'),
            'permission_callback' => '__return_true'
        ));
        
        //绑定登录
        register_rest_route('qk/v1','/bindingLogin',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'bindingLogin'),
            'permission_callback' => '__return_true'
        ));
        
        //重设密码
        register_rest_route('qk/v1','/resetPassword',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'resetPassword'),
            'permission_callback' => '__return_true'
        ));
        /************************************ 登录与注册结束 ************************************************/
        
        /************************************ 文章相关 ************************************************/
        //发布文章
        register_rest_route('qk/v1','/insertPost',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'insertPost'),
            'permission_callback' => '__return_true'
        ));
        
        //获取文章模块内容（分页显示）
        register_rest_route('qk/v1','/getPostList',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getPostList'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route('qk/v1','/getModulePostList',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getModulePostList'),
            'permission_callback' => '__return_true'
        ));
        
        //图片视频文件上传
        register_rest_route('qk/v1','/fileUpload',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'fileUpload'),
            'permission_callback' => '__return_true'
        ));
        
        //文章点赞
        register_rest_route('qk/v1','/postVote',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'postVote'),
            'permission_callback' => '__return_true'
        ));
        
        //发表评论
        register_rest_route('qk/v1','/sendComment',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'sendComment'),
            'permission_callback' => '__return_true'
        ));
        
        //获取评论
        register_rest_route('qk/v1','/getCommentList',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getCommentList'),
            'permission_callback' => '__return_true'
        ));
        
        //评论投票
        register_rest_route('qk/v1','/CommentVote',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'CommentVote'),
            'permission_callback' => '__return_true'
        ));
        
        //评论投票
        register_rest_route('qk/v1','/getEmojiList',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getEmojiList'),
            'permission_callback' => '__return_true'
        ));
        
        //获取用户的评论列表
        register_rest_route('qk/v1','/getUserCommentList',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getUserCommentList'),
            'permission_callback' => '__return_true'
        ));
        
        //获取用户动态列表
        register_rest_route('qk/v1','/getUserDynamicList',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getUserDynamicList'),
            'permission_callback' => '__return_true'
        ));
        
        //获取文章下载数据
        register_rest_route('qk/v1','/getDownloadData',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getDownloadData'),
            'permission_callback' => '__return_true'
        ));
        
        //投诉与举报
        register_rest_route('qk/v1','/getReportTypes',array(
            'methods'=>'get',
            'callback'=>array('Qk\Modules\Common\Report','get_report_types'),
            'permission_callback' => '__return_true'
        ));
        
        //投诉与举报
        register_rest_route('qk/v1','/postReport',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'postReport'),
            'permission_callback' => '__return_true'
        ));
        
        /****************************************课程视频相关**************************************************/
        //获取视频章节播放列表
        register_rest_route('qk/v1','/getVideoList',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getVideoList'),
            'permission_callback' => '__return_true'
        ));
        
        /************************************ 用户相关 ************************************************/
        //关注与取消关注
        register_rest_route('qk/v1','/userFollow',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'userFollow'),
            'permission_callback' => '__return_true'
        ));
        
        //检查是否已经关注
        register_rest_route('qk/v1','/checkFollow',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'checkFollow'),
            'permission_callback' => '__return_true'
        ));
        
        //获取用户粉丝列表
        register_rest_route('qk/v1','/getFansList',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getFansList'),
            'permission_callback' => '__return_true'
        ));
        
        //获取用户关注列表
        register_rest_route('qk/v1','/getFollowList',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getFollowList'),
            'permission_callback' => '__return_true'
        ));
        
        //获取当前用户的附件
        register_rest_route('qk/v1','/getCurrentUserAttachments',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getCurrentUserAttachments'),
            'permission_callback' => '__return_true'
        ));
        
        //文章收藏
        register_rest_route('qk/v1','/userFavorites',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'userFavorites'),
            'permission_callback' => '__return_true'
        ));
        
        //获取用户收藏列表
        register_rest_route('qk/v1','/getUserFavoritesList',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getUserFavoritesList'),
            'permission_callback' => '__return_true'
        ));
        
        //获取用户文章列表
        register_rest_route('qk/v1','/getUserPostList',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getUserPostList'),
            'permission_callback' => '__return_true'
        ));
        
        //获取vip信息
        register_rest_route('qk/v1','/getVipInfo',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getVipInfo'),
            'permission_callback' => '__return_true'
        ));
        
        //获取用户充值余额与积分设置信息
        register_rest_route('qk/v1','/getRechargeInfo',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getRechargeInfo'),
            'permission_callback' => '__return_true'
        ));
        
        //获取用户等级成长信息
        register_rest_route('qk/v1','/getUserLvInfo',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getUserLvInfo'),
            'permission_callback' => '__return_true'
        ));
        
        //修改密码
        register_rest_route('qk/v1','/changePassword',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'changePassword'),
            'permission_callback' => '__return_true'
        ));
        
        //修改当前用户邮箱或手机号
        register_rest_route('qk/v1','/changeEmailOrPhone',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'changeEmailOrPhone'),
            'permission_callback' => '__return_true'
        ));
        
        //获取用户设置项信息
        register_rest_route('qk/v1','/getUserSettings',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getUserSettings'),
            'permission_callback' => '__return_true'
        ));
        
        //保存用户信息
        register_rest_route('qk/v1','/saveUserInfo',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'saveUserInfo'),
            'permission_callback' => '__return_true'
        ));
        
        //保存用户头像
        register_rest_route('qk/v1','/saveAvatar',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'saveAvatar'),
            'permission_callback' => '__return_true'
        ));
        
        //用户签到
        register_rest_route('qk/v1','/userSignin',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'userSignin'),
            'permission_callback' => '__return_true'
        ));
        
        //获取用户签到信息
        register_rest_route('qk/v1','/getUserSignInfo',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getUserSignInfo'),
            'permission_callback' => '__return_true'
        ));
        
        //获取用户的订单
        register_rest_route('qk/v1','/getUserOrders',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getUserOrders'),
            'permission_callback' => '__return_true'
        ));
        
        //获取任务列表数据
        register_rest_route('qk/v1','/getTaskData',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getTaskData'),
            'permission_callback' => '__return_true'
        ));
        
        //获取积分、余额记录
        register_rest_route('qk/v1','/getUserRecords',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getUserRecords'),
            'permission_callback' => '__return_true'
        ));
        
        //解除绑定社交账户
        register_rest_route('qk/v1','/unBinding',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'unBinding'),
            'permission_callback' => '__return_true'
        ));
        
        //提现申请
        register_rest_route('qk/v1','/cashOut',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'cashOut'),
            'permission_callback' => '__return_true'
        ));
        
        //保存用户提现收款二维码
        register_rest_route('qk/v1','/saveQrcode',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'saveQrcode'),
            'permission_callback' => '__return_true'
        ));
        
        //获取用户收款二维码
        register_rest_route('qk/v1','/getUserQrcode',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getUserQrcode'),
            'permission_callback' => '__return_true'
        ));
        
        /************************************ 分销 ************************************************/
        
        register_rest_route('qk/v1','/getUserPartner',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getUserPartner'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route('qk/v1','/getUserRebateOrders',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getUserRebateOrders'),
            'permission_callback' => '__return_true'
        ));
        
        /************************************ 用户消息相关 ************************************************/
        
        //获取用户未读信息
        register_rest_route('qk/v1','/getUnreadMsgCount',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getUnreadMsgCount'),
            'permission_callback' => '__return_true'
        ));
        
        //获取联系人列表
        register_rest_route('qk/v1','/getContact',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getContact'),
            'permission_callback' => '__return_true'
        ));
        
        //获取联系人列表
        register_rest_route('qk/v1','/getContactList',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getContactList'),
            'permission_callback' => '__return_true'
        ));
        
        //获取消息列表
        register_rest_route('qk/v1','/getMessageList',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getMessageList'),
            'permission_callback' => '__return_true'
        ));
        
        //发送消息
        register_rest_route('qk/v1','/sendMessage',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'sendMessage'),
            'permission_callback' => '__return_true'
        ));
        
        /************************************ 弹幕相关 ************************************************/
         
         //发送弹幕
        register_rest_route('qk/v1','/sendDanmaku',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'sendDanmaku'),
            'permission_callback' => '__return_true'
        ));
        
        //获取弹幕
        register_rest_route('qk/v1','/getDanmaku',array(
            'methods'=>'get',
            'callback'=>array(__CLASS__,'getDanmaku'),
            'permission_callback' => '__return_true'
        ));
        
        /************************************ 搜索相关 ************************************************/
         
        //获取搜索建议
        register_rest_route('qk/v1','/getSearchSuggest',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getSearchSuggest'),
            'permission_callback' => '__return_true'
        ));
        
        
        /************************************ 订单与支付相关 ************************************************/
        
        //开始支付 创建临时订单
        register_rest_route('qk/v1','/buildOrder',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'buildOrder'),
            'permission_callback' => '__return_true'
        ));
        
        //删除订单
        register_rest_route('qk/v1','/deleteOrder',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'deleteOrder'),
            'permission_callback' => '__return_true'
        ));
        
         //余额支付
        register_rest_route('qk/v1','/balancePay',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'balancePay'),
            'permission_callback' => '__return_true'
        ));

        //积分支付
        register_rest_route('qk/v1','/creditPay',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'creditPay'),
            'permission_callback' => '__return_true'
        ));

        //支付检查确认
        register_rest_route('qk/v1','/payCheck',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'payCheck'),
            'permission_callback' => '__return_true'
        ));
        
        //卡密充值与邀请码使用
        register_rest_route('qk/v1','/cardPay',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'cardPay'),
            'permission_callback' => '__return_true'
        ));
        
        //验证密码
        register_rest_route('qk/v1','/passwordVerify',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'passwordVerify'),
            'permission_callback' => '__return_true'
        ));
        
        //获取允许的付款方式
        register_rest_route('qk/v1','/allowPayType',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'allowPayType'),
            'permission_callback' => '__return_true'
        ));
        
        /************************************ 圈子相关 ************************************************/
        //发布帖子
        register_rest_route('qk/v1','/insertMoment',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'insertMoment'),
            'permission_callback' => '__return_true'
        ));
        
        //搜索圈子与话题
        register_rest_route('qk/v1','/getSearchCircle',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getSearchCircle'),
            'permission_callback' => '__return_true'
        ));
        
        //获取所有圈子
        register_rest_route('qk/v1','/getAllCircles',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getAllCircles'),
            'permission_callback' => '__return_true'
        ));
        
        //获取话题
        register_rest_route('qk/v1','/getTopics',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getTopics'),
            'permission_callback' => '__return_true'
        ));
        
        //获取用户在当前圈子能力及编辑器设置
        register_rest_route('qk/v1','/getUserCircleCapabilities',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getUserCircleCapabilities'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route('qk/v1','/getMomentList',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getMomentList'),
            'permission_callback' => '__return_true'
        ));
        
        //获取编辑帖子数据
        register_rest_route('qk/v1','/getEditMomentData',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getEditMomentData'),
            'permission_callback' => '__return_true'
        ));
        
        //帖子加精
        register_rest_route('qk/v1','/setMomentBest',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'setMomentBest'),
            'permission_callback' => '__return_true'
        ));
        
        //帖子置顶
        register_rest_route('qk/v1','/setMomentSticky',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'setMomentSticky'),
            'permission_callback' => '__return_true'
        ));
        
        //删除帖子
        register_rest_route('qk/v1','/deleteMoment',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'deleteMoment'),
            'permission_callback' => '__return_true'
        ));
        
        //审核帖子
        register_rest_route('qk/v1','/changeMomentStatus',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'changeMomentStatus'),
            'permission_callback' => '__return_true'
        ));
        
        //创建圈子
        register_rest_route('qk/v1','/createCircle',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'createCircle'),
            'permission_callback' => '__return_true'
        ));
        
        //加入圈子
        register_rest_route('qk/v1','/joinCircle',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'joinCircle'),
            'permission_callback' => '__return_true'
        ));
        
        //获取圈子权限数据
        register_rest_route('qk/v1','/getCircleRoleData',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getCircleRoleData'),
            'permission_callback' => '__return_true'
        ));
        
        //创建话题
        register_rest_route('qk/v1','/createTopic',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'createTopic'),
            'permission_callback' => '__return_true'
        ));
        
        //创建话题
        register_rest_route('qk/v1','/getCircleData',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getCircleData'),
            'permission_callback' => '__return_true'
        ));
        
        //获取分类
        register_rest_route('qk/v1','/getCircleCats',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getCircleCats'),
            'permission_callback' => '__return_true'
        ));
        
        //获取圈子管理设置
        register_rest_route('qk/v1','/getManageCircle',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getManageCircle'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route('qk/v1','/getCircleUsers',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getCircleUsers'),
            'permission_callback' => '__return_true'
        ));
        
        //圈子用户搜索
        register_rest_route('qk/v1','/circleSearchUsers',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'circleSearchUsers'),
            'permission_callback' => '__return_true'
        ));
        
        //邀请用户加入圈子
        register_rest_route('qk/v1','/inviteUserJoinCircle',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'inviteUserJoinCircle'),
            'permission_callback' => '__return_true'
        ));
        
        //设置圈子版主
        register_rest_route('qk/v1','/setUserCircleStaff',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'setUserCircleStaff'),
            'permission_callback' => '__return_true'
        ));
        
        //移除圈子用户或版主
        register_rest_route('qk/v1','/removeCircleUser',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'removeCircleUser'),
            'permission_callback' => '__return_true'
        ));
        
        //获取圈子文章管理列表
        register_rest_route('qk/v1','/getManageMomentList',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getManageMomentList'),
            'permission_callback' => '__return_true'
        ));
        
        //获取帖子视频List
        register_rest_route('qk/v1','/getMomentVideoList',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getMomentVideoList'),
            'permission_callback' => '__return_true'
        ));
        
        /************************************ 认证服务相关 ************************************************/
        
        //获取认证相关信息
        register_rest_route('qk/v1','/getVerifyInfo',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getVerifyInfo'),
            'permission_callback' => '__return_true'
        ));
        
        //获取用户认证信息
        register_rest_route('qk/v1','/getUserVerifyInfo',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'getUserVerifyInfo'),
            'permission_callback' => '__return_true'
        ));
        
        //认证申请
        register_rest_route('qk/v1','/submitVerify',array(
            'methods'=>'post',
            'callback'=>array(__CLASS__,'submitVerify'),
            'permission_callback' => '__return_true'
        ));
    }

    
    /**
     * 用户注册
     *
     * @param object $request
     *
     * @return void
     * 
     * @version 1.0.0
     * @since 2023
     */
    public static function regeister($request){
        if(!qk_check_repo()) return new \WP_Error('regeister_error','操作频次过高',array('status'=>403));
        
        $res = Login::regeister($request->get_params());
        
        if(isset($res['error'])){
            return new \WP_Error('regeister_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response(array('msg'=>$res),200);
        }
    }
    
    /**
     * 社交登录绑定用户名
     *
     * @param object $request username:用户名是手机或者邮箱
     * 
     * @return string 验证码token
     * 
     * @version 1.0.0
     * @since 2023
     */
    public static function sendCode($request){
        if(!qk_check_repo()) return new \WP_Error('sendCode_error','操作频次过高',array('status'=>403));
        $res = Login::send_code($request);
        if(isset($res['error'])){
            return new \WP_Error('sendCode_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response(array('msg'=>$res),200);
        }
    }
    
    /**
     * 获取允许的社交登录方式
     * 
     * @version 1.1
     * @since 2023
     */
    public static function getEnabledOauths($request){
        $res = Oauth::get_enabled_oauths();
        if(isset($res['error'])){
            return new \WP_Error('sendCode_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    /**
     * 社交登录
     * 
     * @version 1.1
     * @since 2023
     */
    public static function socialLogin($request){
        $res = Oauth::social_oauth_login($request['type']);
        if(isset($res['error'])){
            return new \WP_Error('social_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    /**
     * 检查邀请码
     * 
     * @version 1.1
     * @since 2023
     */
    public static function checkInviteCode($request){
        if(!qk_check_repo($request['invite_code'])) return new \WP_Error('invite_error','别点的太快啦！',array('status'=>403));
        
        $res = Invite::checkInviteCode($request['invite_code']);
        
        if(isset($res['error'])){
            return new \WP_Error('social_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    /**
     * 绑定登录
     * 
     * @version 1.1
     * @since 2023
     */
    public static function bindingLogin($request){
        if(!qk_check_repo($request['invite_code'])) return new \WP_Error('binding_login_error','别点的太快啦！',array('status'=>403));

        if(isset($request['token'])){
            $res = OAuth::binding_login($request);
        }else{
            return new \WP_Error('binding_login_error','数据错误',array('status'=>403));
        }
        
        if(isset($res['error'])){
            return new \WP_Error('binding_login_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    /**
     * 重设密码
     * 
     * @version 1.1
     * @since 2023
     */
    public static function resetPassword($request){
        $res = Login::rest_password($request);

        if(isset($res['error'])){
            return new \WP_Error('login_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    /************************************ 文章相关 ************************************************/
    //发布文章
    public static function insertPost($request){
        $res = Post::insert_post($request->get_params());
        
        if(isset($res['error'])){
            return new \WP_Error('insertPost_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    /**
     * 获取文章列表
     *
     * @param array $request
     *
     * @return array
     * 
     * @version 1.0.0
     * @since 2023
     */
    public static function getPostList($request){
        $type = str_replace('-','_',$request['post_type']);

        if(!method_exists('Qk\Modules\Templates\Modules\Posts',$type)) return;

        return Posts::$type($request,$request['post_i'],true);
    }
    
    /**
     * 获取模块文章列表
     *
     * @param array $request 请求参数，包含以下键值：
     *      - index: 模块索引，int类型
     *      - id: 文章分类ID，int类型，可选参数
     *      - post_paged: 文章分页数，int类型，可选参数
     * 
     * @return string 返回文章列表HTML代码
     */
    public static function getModulePostList($request){

        // 获取模块索引
        $index = (int)$request['index'] - 1;
    
        // 获取模板设置
        $template = qk_get_option('qk_template_index');
    
        // 简化代码
        $module = isset($template[$index]) ? $template[$index] : array();
    
        // 判断模块是否存在
        if(!empty($module)){
            
            // 获取页面宽度
            //$module['width'] = qk_get_page_width($module['show_widget']);
            
            //换一换
            if(isset($request['orderby']) && $request['orderby'] == 'random'){
                $module['post_order'] = $request['orderby'];
            }

            // 设置文章分类
            if(!empty($request['id'])){
                
                if(term_exists($request['id'], 'category')) {
                    $module['post_cat'] = array((int)$request['id']);
                    $module['video_cat'] = array();
                    $module['_post_type'] = 'post';
                }
                
                if(term_exists($request['id'], 'video_cat')) {
                    $module['post_cat'] = array();
                    $module['_post_type'] = 'video';
                    $module['video_cat'] = array((int)$request['id']);
                }
                
                // if(term_exists($request['id'], 'post_tag')) {
                //     $module['post_cat'] = array();
                //     $module['video_cat'] = array();
                //     $module['_post_type'] = 'post';
                //     $module['post_tag'] = array((int)$request['id']);
                // }
                
                // 获取分类对象
                // $term = get_term($term_id);
                
                // if ($term && !is_wp_error($term)) {
                //     if ($term->taxonomy === 'video_cat') {
                //         // 分类 ID 属于 'video_cat'
                //         echo '分类 ID 属于 video_cat';
                //     } elseif ($term->taxonomy === 'category') {
                //         // 分类 ID 属于 'category'
                //         echo '分类 ID 属于 category';
                //     } else {
                //         // 分类 ID 属于其他分类法
                //         echo '分类 ID 属于其他分类法';
                //     }
                // } 
                
            }else{
                $module['post_cat'] = $module['post_cat'];
            }

            // 设置文章分页
            $module['post_paged'] = (int)$request['paged'];
            
            //是否是移动端
            $module['is_mobile'] = wp_is_mobile();
            
            // 返回文章列表HTML代码
            $posts = new Posts;
            $data = $posts->init($module,(int)$request['index'],true);
            $data['post_type'] = $module['post_type'];

            if($data['count'] < 1) {
                $data['data'] = qk_get_empty('暂无内容','empty.svg');
            }
            
            return $data;
        }
    
        // 如果模块不存在，则返回空字符串
        return '';
    }
    
    /**
     * 图片视频文件上传
     *
     * @param object $request
     *
     * @return void
     * 
     * @version 1.0.0
     * @since 2023
     */
    public static function fileUpload($request){

        $res = FileUpload::file_upload($request->get_params());
        if(isset($res['error'])){
            return new \WP_Error('user_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    /**
     * 图片视频文件上传
     *
     * @param object $request
     *
     * @return void
     * 
     * @version 1.0.0postVote
     * @since 2023
     */
    public static function postVote($request){

        $res = Post::post_vote($request['type'],$request['post_id']);
        if(isset($res['error'])){
            return new \WP_Error('user_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    /**
     * 发表评论
     *
     * @param object $request
     *
     * @return void
     * 
     * @version 1.0.0
     * @since 2023
     */
     
    public static function sendComment($request){

        $res = Comment::send_comment($request->get_params());
        if(isset($res['error'])){
            return new \WP_Error('comment_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    /**
     * 获取评论列表
     *
     * @param object $request
     *
     * @return void
     * 
     * @version 1.0.0
     * @since 2023
     */
    public static function getCommentList($request){

        $res = Comment::get_comment_list($request->get_params());
        if(isset($res['error'])){
            return new \WP_Error('comment_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    /**
     * 评论投票
     *
     * @param object $request
     *
     * @return void
     * 
     * @version 1.0.0
     * @since 2023
     */
    public static function CommentVote($request){

        $res = Comment::comment_vote($request['type'],$request['comment_id']);
        if(isset($res['error'])){
            return new \WP_Error('comment_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    /**
     * 获取表情列表
     *
     * @return void
     * 
     * @version 1.0.0
     * @since 2023
     */
    public static function getEmojiList(){
        
        $smilies =  qk_get_option('comment_smilies_arg');
        
        if(is_array($smilies) && $smilies) {
            $res['list'] = $smilies;
        }else{
            $res['error'] = "暂未有表情设置";
        }
        
        if(isset($res['error'])){
            return new \WP_Error('comment_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    /**
     * 获取用户的评论列表
     *
     * @param [type] $request
     *
     * @return void
     * @author 青青草原上
     * @version 1.0.0
     * @since 2023
     */
    public static function getUserCommentList($request){
        $res = Comment::get_user_comment_list($request['user_id'],$request['paged'],$request['size']);

        if(isset($res['error'])){
            return new \WP_Error('comment_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    /**
     * 获取用户的动态列表
     *
     * @param [type] $request
     *
     * @return void
     * @author 青青草原上
     * @version 1.0.0
     * @since 2023
     */
    public static function getUserDynamicList($request){
        $res = User::get_user_dynamic_list($request['user_id'],$request['paged'],$request['size']);

        if(isset($res['error'])){
            return new \WP_Error('dynamic_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //获取内页中的下载数据
    public static function getDownloadData($request){
        $res = Post::get_post_download_data($request['post_id']);

        if(isset($res['error'])){
            return new \WP_Error('download_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //投诉与举报
    public static function postReport($request){
        $res = Report::report($request->get_params());

        if(isset($res['error'])){
            return new \WP_Error('post_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    /****************************************课程视频相关**************************************************/
    
    //获取视频章节播放列表
    public static function getVideoList($request){
        $res = Player::get_video_list((int)$request['post_id']);

        if(isset($res['error'])){
            return new \WP_Error('video_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        } 
    }
    
    /************************************ 用户相关 ************************************************/
    
    //关注与取消关注
    public static function userFollow($request){
        $res = User::user_follow_action($request['user_id']);

        if(isset($res['error'])){
            return new \WP_Error('user_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        } 
    }
    
    //检查是否已经关注
    public static function checkFollow($request){
        $res = User::check_follow($request['user_id']);

        if(isset($res['error'])){
            return new \WP_Error('user_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        } 
    }
    
    //获取用户的关注列表
    public static function getFollowList($request){
        $res = User::get_follow_list($request['user_id'],$request['paged'],$request['size']);

        if(isset($res['error'])){
            return new \WP_Error('user_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        } 
    }
    
    //获取用户粉丝列表
    public static function getFansList($request){
        $res = User::get_fans_list($request['user_id'],$request['paged'],$request['size']);

        if(isset($res['error'])){
            return new \WP_Error('user_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        } 
    }
    
    //获取当前用户的附件
    public static function getCurrentUserAttachments($request){
        $res = User::get_current_user_attachments($request['type'],$request['paged']);

        if(isset($res['error'])){
            return new \WP_Error('user_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        } 
    }
    
    //用户收藏与取消收藏
    public static function userFavorites($request){
        $res = User::user_favorites($request['post_id']);

        if(isset($res['error'])){
            return new \WP_Error('user_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        } 
    }
    
    //获取用户收藏列表
    public static function getUserFavoritesList($request){
        $res = User::get_user_favorites_list($request['user_id'],$request['paged'],$request['size'],$request['post_type']);

        if(isset($res['error'])){
            return new \WP_Error('user_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        } 
    }
    
    //获取用户文章列表
    public static function getUserPostList($request){

        $res = User::get_user_posts($request['paged'],$request['size'],$request['post_type']);

        if(isset($res['error'])){
            return new \WP_Error('user_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        } 
    }
    
    //获取vip数据
    public static function getVipInfo($request){
        $res = User::get_vip_info();

        if(isset($res['error'])){
            return new \WP_Error('pay_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //获取用户余额与积分数据
    public static function getRechargeInfo($request){
        $res = User::get_recharge_info();

        if(isset($res['error'])){
            return new \WP_Error('pay_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //获取用户等级成长信息
    public static function getUserLvInfo($request){
        $res = User::get_user_lv_info();

        if(isset($res['error'])){
            return new \WP_Error('user_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //修改密码
    public static function changePassword($request) {
        if(!qk_check_repo()) return new \WP_Error('user_error','操作频次过高',array('status'=>403));
        
        $res = User::change_password($request['password'],$request['confirm_password']);
        
        if(isset($res['error'])){
            return new \WP_Error('user_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //修改或绑定手机号
    public static function changeEmailOrPhone($request) {
        if(!qk_check_repo()) return new \WP_Error('user_error','操作频次过高',array('status'=>403));
        
        $res = User::change_email_or_phone($request->get_params());
        
        if(isset($res['error'])){
            return new \WP_Error('user_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //获取用户设置项信息
    public static function getUserSettings($request){
        $res = User::get_user_settings();

        if(isset($res['error'])){
            return new \WP_Error('user_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //保存用户信息
    public static function saveUserInfo($request) {
        if(!qk_check_repo()) return new \WP_Error('user_error','操作频次过高',array('status'=>403));
        
        $res = User::save_user_info($request->get_params());
        
        if(isset($res['error'])){
            return new \WP_Error('user_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //保存用户头像
    public static function saveAvatar($request){
        if(!qk_check_repo($request['id'])) return new \WP_Error('user_error','操作频次过高',array('status'=>403));
        $res = User::save_avatar($request['url'],$request['id']);

        if(isset($res['error'])){
            return new \WP_Error('user_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    
    //用户签到
    public static function userSignin($request){
        if(!qk_check_repo($request['id'])) return new \WP_Error('user_error','操作频次过高',array('status'=>403));
        $res = Signin::user_signin();

        if(isset($res['error'])){
            return new \WP_Error('user_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //获取用户签到信息
    public static function getUserSignInfo($request){
        //if(!qk_check_repo($request['id'])) return new \WP_Error('user_error','操作频次过高',array('status'=>403));
        $res = Signin::get_sign_in_info($request['date']);

        if(isset($res['error'])){
            return new \WP_Error('user_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //获取用户的订单列表数据
    public static function getUserOrders($request){
        $res = Orders::get_user_orders($request['user_id'],$request['paged'],isset($request['state']) ? $request['state'] : 6);

        if(isset($res['error'])){
            return new \WP_Error('user_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //获取任务列表数据
    public static function getTaskData($request){
        $res = Task::get_task_data($request['user_id'],$request['key']);

        if(isset($res['error'])){
            return new \WP_Error('user_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //获取积分、余额记录
    public static function getUserRecords($request){
        $res = Record::get_record_list($request->get_params());

        if(isset($res['error'])){
            return new \WP_Error('user_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //解除绑定社交账户
    public static function unBinding($request){
        if(!qk_check_repo()) return new \WP_Error('user_error',__('操作频次过高','qk'),array('status'=>403));
        $res = User::un_binding($request['type']);

        if(isset($res['error'])){
            return new \WP_Error('user_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //提现申请
    public static function cashOut($request){
        if(!qk_check_repo()) return new \WP_Error('user_error',__('操作频次过高','qk'),array('status'=>403));
        $res = User::cash_out($request['money'],$request['type']);

        if(isset($res['error'])){
            return new \WP_Error('user_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //保存收款码
    public static function saveQrcode($request){
        if(!qk_check_repo()) return new \WP_Error('user_error',__('操作频次过高','qk'),array('status'=>403));
        $res = User::save_qrcode($request['type'],$request['url']);

        if(isset($res['error'])){
            return new \WP_Error('user_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //获取用户收款二维码
    public static function getUserQrcode($request){
        $user_id = get_current_user_id();
        $res = User::get_user_qrcode($user_id);
        if(isset($res['error'])){
            return new \WP_Error('user_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    /************************************ 分销 ************************************************/
    
    //获取当前用户所关联的用户
    public static function getUserPartner($request){
        $res = Distribution::get_user_partner($request['paged']);

        if(isset($res['error'])){
            return new \WP_Error('shop_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //获取用户返佣订单
    public static function getUserRebateOrders($request){
        $res = Distribution::get_user_rebate_orders($request['paged']);

        if(isset($res['error'])){
            return new \WP_Error('shop_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    /************************************ 用户信息相关 ************************************************/
    
    //获取用户未读信息数量
    public static function getUnreadMsgCount($request){
        $res = Message::get_unread_message_count();

        if(isset($res['error'])){
            return new \WP_Error('user_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //获取联系人
    public static function getContact($request){
        $res = Message::get_contact($request['user_id']);

        if(isset($res['error'])){
            return new \WP_Error('msg_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //获取信息列表
    public static function getContactList($request){
        $res = Message::get_contact_list($request['paged']);

        if(isset($res['error'])){
            return new \WP_Error('msg_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //获取信息列表
    public static function getMessageList($request){
        $res = Message::get_message_list($request->get_params());

        if(isset($res['error'])){
            return new \WP_Error('msg_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //发送消息
    public static function sendMessage($request){
        if(!qk_check_repo()) return new \WP_Error('msg_error',__('操作频次过高','qk'),array('status'=>403));
        
        $res = Message::send_message($request['user_id'],$request['content'],$request['image_id']);

        if(isset($res['error'])){
            return new \WP_Error('msg_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    /************************************ 弹幕相关 ************************************************/
    
    public static function sendDanmaku($request){
        $res = Danmaku::send_danmaku($request->get_params());

        if(isset($res['error'])){
            return new \WP_Error('danmaku_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        } 
    }
    
    public static function getDanmaku($request){
        $res = Danmaku::get_danmaku($request['cid']);

        if(isset($res['error'])){
            return new \WP_Error('danmaku_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        } 
    }
    
    /************************************ 搜索相关 ************************************************/
    
    //获取搜索建议
    public static function getSearchSuggest($request){
        $res = Search::get_search_suggest($request['search']);

        return $res;
    }
    
    /************************************ 订单与支付相关 ************************************************/
    //创建订单
    public static function buildOrder($request){
        if(!qk_check_repo()) return new \WP_Error('user_error',__('操作频次过高','qk'),array('status'=>403));
        $res = Orders::build_order($request->get_params());

        if(isset($res['error'])){
            return new \WP_Error('order_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //删除订单
    public static function deleteOrder($request){
        if(!qk_check_repo()) return new \WP_Error('user_error',__('操作频次过高','qk'),array('status'=>403));
        $res = Orders::delete_order($request['user_id'],$request['order_id']);

        if(isset($res['error'])){
            return new \WP_Error('order_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //余额支付
    public static function balancePay($request){
        if(!qk_check_repo($request['order_id'])) return new \WP_Error('user_error',__('操作频次过高','qk'),array('status'=>403));
        $res = Pay::balance_pay($request['order_id']);

        if(isset($res['error'])){
            return new \WP_Error('pay_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }

    //积分支付
    public static function creditPay($request){
        if(!qk_check_repo($request['order_id'])) return new \WP_Error('user_error',__('操作频次过高','qk'),array('status'=>403));
        $res = Pay::credit_pay($request['order_id']);

        if(isset($res['error'])){
            return new \WP_Error('pay_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //检查支付结果
    public static function payCheck($request){

        $res = Pay::pay_check($request['order_id']);

        return $res;
    }
    
    /**
     * 激活码或卡密充值
     * 
     * @return array
     * 
     * @version 1.0.0
     * @since 2023
     */
    public static function cardPay($request){
        
        $res = Card::card_pay($request['code']);
        
        if(isset($res['error'])){
            return new \WP_Error('pay_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    /**
     * 密码验证
     * 
     * @return array
     * 
     * @version 1.0.3
     * @since 2023/9/13
     */
    public static function passwordVerify($request){

        $code = trim($request['code'], " \t\n\r\0\x0B\xC2\xA0") ?? '';
        $post_id = (int)$request['post_id'] ?? 0;
        $type = isset($request['type']) ?? 'post';
        
        if(!$post_id) return new \WP_Error('pay_error','文章不存在',array('status'=>403));
        if(!$code) return new \WP_Error('pay_error','密码错误',array('status'=>403));
        
        $verification_code = qk_get_option('password_verify')['code'];
        
        if($type == 'circle'){
            $password = get_term_meta($post_id,'qk_circle_password',true);
        }else{
            $password = get_post_meta($post_id,'qk_post_password',true);
        }
        
        //支持个人密码和官方设置的密码
        if ($verification_code != $code && $password != $code) {
            return new \WP_Error('pay_error','密码验证错误',array('status'=>403));
        }

        return new \WP_REST_Response(array('msg'=>'密码验证成功，将在3秒后自动刷新当前页面！'),200);
    }
    
    //允许使用的支付付款方式
    public static function allowPayType($request){
        $res = Pay::allow_pay_type($request['order_type']);

        if(isset($res['error'])){
            return new \WP_Error('pay_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    /************************************ 圈子与话题相关 ************************************************/
    
    //发布帖子
    public static function insertMoment($request){
        $res = Circle::insert_Moment($request->get_params());
        
        if(isset($res['error'])){
            return new \WP_Error('insertMoment_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
     //搜索圈子与话题
    public static function getSearchCircle($request){
        $res = Circle::get_search_circle($request->get_params());
        
        if(isset($res['error'])){
            return new \WP_Error('search_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //获取所有圈子并按热门排序
    public static function getAllCircles($request){
        $res = Circle::get_all_circles();
        
        if(isset($res['error'])){
            return new \WP_Error('search_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //获取话题
    public static function getTopics($request){
        $res = Circle::get_topics($request->get_params());
        
        if(isset($res['error'])){
            return new \WP_Error('search_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //获取用户在当前圈子能力及编辑器设置
    public static function getUserCircleCapabilities($request){
        $user_id = get_current_user_id();
        
        $res = Circle::check_insert_moment_role($user_id,$request['circle_id'],true);
        
        if(isset($res['error'])){
            return new \WP_Error('circle_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //获取帖子列表
    public static function getMomentList($request){
        $tax = get_term( (int)$request['circle_id'] );
        $taxonomy = isset($tax->taxonomy) ? $tax->taxonomy : '';
        $term_id = isset($tax->term_id) ? $tax->term_id : 0;
        
        $tabbar = Circle::get_tabbar($tax);
        $default_index = Circle::get_default_tabbar_index($tax);
        
        $index = isset($request['index']) ? (int)$request['index'] : $default_index;
        $args = isset($tabbar[$index]) ? $tabbar[$index] : array();
        
        if($term_id){
            if($taxonomy == 'circle_cat' && !isset($args['circle_cat'])) {
                $args['circle_cat'] = $term_id;
            }
        
            if($taxonomy == 'topic' && !isset($args['topic'])) {
                $args['topic'] = $term_id;
            }
        }
        
        if(!empty($request['orderby'])) {
            $args['orderby'] = $request['orderby'];
        }
        
        $args['paged'] = (int)$request['paged'];
        $args['size'] = (int)$request['size'];

        $res = Circle::get_moment_list($args);
        
        if(isset($res['error'])){
            return new \WP_Error('circle_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //获取编辑帖子数据
    public static function getEditMomentData($request){

        $res = Circle::get_edit_moment_data($request['post_id']);

        if(isset($res['error'])){
            return new \WP_Error('circle_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //加精
    public static function setMomentBest($request){
        if(!qk_check_repo()) return new \WP_Error('user_error','操作频次过高',array('status'=>403));
        $res = Circle::set_moment_best($request['post_id']);

        if(isset($res['error'])){
            return new \WP_Error('circle_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //置顶
    public static function setMomentSticky($request){
        if(!qk_check_repo()) return new \WP_Error('user_error','操作频次过高',array('status'=>403));
        $res = Circle::set_moment_sticky($request['post_id']);

        if(isset($res['error'])){
            return new \WP_Error('circle_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //删除帖子
    public static function deleteMoment($request){
        if(!qk_check_repo()) return new \WP_Error('user_error','操作频次过高',array('status'=>403));
        $res = Circle::delete_moment($request['post_id']);

        if(isset($res['error'])){
            return new \WP_Error('circle_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //审核帖子
    public static function changeMomentStatus($request){
        if(!qk_check_repo()) return new \WP_Error('user_error','操作频次过高',array('status'=>403));
        $res = Circle::change_moment_status($request['post_id']);

        if(isset($res['error'])){
            return new \WP_Error('circle_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //创建圈子
    public static function createCircle($request){
        if(!qk_check_repo()) return new \WP_Error('user_error','操作频次过高',array('status'=>403));
        $res = Circle::create_circle($request->get_params());

        if(isset($res['error'])){
            return new \WP_Error('circle_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //加入圈子
    public static function joinCircle($request){
        if(!qk_check_repo()) return new \WP_Error('user_error','操作频次过高',array('status'=>403));
        $res = Circle::join_circle($request);

        if(isset($res['error'])){
            return new \WP_Error('circle_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //获取圈子权限数据
    public static function getCircleRoleData($request){
        $user_id = get_current_user_id();
        $res = Circle::get_circle_role_data($user_id,(int)$request['circle_id']);

        if(isset($res['error'])){
            return new \WP_Error('circle_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    public static function createTopic($request){
        if(!qk_check_repo()) return new \WP_Error('user_error','操作频次过高',array('status'=>403));
        $res = Circle::create_topic($request->get_params());

        if(isset($res['error'])){
            return new \WP_Error('circle_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //获取圈子数据
    public static function getCircleData($request){
        $res = Circle::get_circle_data((int)$request['circle_id']);
                
        if(isset($res['error']) ){
            return new \WP_Error('circle_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    public static function getCircleCats() {
        $res = Circle::get_circle_cats();
        if(isset($res['error']) ){
            return new \WP_Error('circle_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //获取圈子管理设置
    public static function getManageCircle($request) {
        $res = Circle::get_manage_circle((int)$request['circle_id']);
        if(isset($res['error']) ){
            return new \WP_Error('circle_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //获取圈子用户列表
    public static function getCircleUsers($request) {
        $res = Circle::get_circle_users($request->get_params());
        if(isset($res['error']) ){
            return new \WP_Error('circle_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //获取圈子用户搜索
    public static function circleSearchUsers($request) {
        $res = Circle::circle_search_users($request['key'],(int)$request['circle_id']);
        if(isset($res['error']) ){
            return new \WP_Error('circle_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //邀请用户加入圈子
    public static function inviteUserJoinCircle($request) {
        $res = Circle::invite_user_join_circle((int)$request['user_id'],(int)$request['circle_id']);
        if(isset($res['error']) ){
            return new \WP_Error('circle_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //设置版主
    public static function setUserCircleStaff($request) {
        $res = Circle::set_user_circle_staff((int)$request['user_id'],(int)$request['circle_id']);
        if(isset($res['error']) ){
            return new \WP_Error('circle_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //移除圈子用户或版主
    public static function removeCircleUser($request) {
        $res = Circle::remove_circle_user((int)$request['user_id'],(int)$request['circle_id']);
        if(isset($res['error']) ){
            return new \WP_Error('circle_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //获取管理帖子列表
    public static function getManageMomentList($request){
        
        $args['circle_cat'] = (int)$request['circle_id'];
        $args['orderby'] = $request['orderby'];
        
        $args['paged'] = (int)$request['paged'];
        $args['size'] = (int)$request['size'];
        $args['post_status'] = !empty($request['post_status']) ? array($request['post_status']) : array();
        $args['search'] = isset($request['search']) ? $request['search'] :'';
        
        $current_user_id = get_current_user_id();
        $role = Circle::check_insert_moment_role($current_user_id, (int)$request['circle_id']);

        if(empty($role['is_circle_staff']) && empty($role['is_admin'])){
            $res = array('error'=>'您无权管理圈子文章');
        } else{
            $res = Circle::get_moment_list($args,false);
        }
        
        if(isset($res['error'])){
            return new \WP_Error('circle_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //获取帖子视频List
    public static function getMomentVideoList($request){
        $res = Circle::get_moment_attachment((int)$request['post_id']);

        if(isset($res['error'])){
            return new \WP_Error('video_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res['video'],200);
        } 
    }
    
    /************************************ 认证服务相关 ************************************************/
    //获取认证相关信息
    public static function getVerifyInfo(){
        $res = Verify::get_verify_info();

        if(isset($res['error'])){
            return new \WP_Error('verify_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //获取认证相关信息
    public static function getUserVerifyInfo(){
        $res = Verify::get_user_verify_info();

        if(isset($res['error'])){
            return new \WP_Error('verify_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
    
    //提交认证
    public static function submitVerify($request){
        if(!qk_check_repo()) return new \WP_Error('verify_error','操作频次过高',array('status'=>403));
        $res = Verify::submit_verify($request->get_params());

        if(isset($res['error'])){
            return new \WP_Error('verify_error',$res['error'],array('status'=>403));
        }else{
            return new \WP_REST_Response($res,200);
        }
    }
}