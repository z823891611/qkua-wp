<?php
$verify_open = qk_get_option('verify_open');
if(!$verify_open){
    wp_safe_redirect(QK_HOME_URI.'/404');
    exit;
}

/**
 * verify页面
 */
get_header();

$verify_page = qk_get_option('qk_verify_page');

?>

<div class="qk-single-content wrapper" style="height: 100%;">
    <div id="verify" class="content-area verify-page" >
        <main id="main" class="site-main">
            <div class="verify-header">
                <h1><i class="ri-verified-badge-fill" style=" margin-right: 12px;"></i><?php echo $verify_page['title'] ?></h1>
            <div class="desc"><?php echo $verify_page['desc'] ?></div>
            </div>
            <div class="verify-body">
                <div class="steps-warp">
                    <div :class="['step-item',{'active':step >= 1}]">
                        <span>1</span>
                        <span>选择类型</span>
                    </div>
                    <div :class="['step-item',{'active':step >= 2}]">
                        <span>2</span>
                        <span>填写信息</span>
                    </div>
                    <div :class="['step-item',{'active':step >= 3}]">
                        <span>3</span>
                        <span>审核信息</span>
                    </div>
                    <div :class="['step-item',{'active':step >= 4}]">
                        <span>4</span>
                        <span>认证结果</span>
                    </div>
                </div>
                <!--第一步-->
                <div class="step-one-wrap" v-if="step == 1" v-cloak>
                    <div class="verify-type-wrap">
                        <div class="verify-type-container">
                            <div class="type-item" v-for="(item,index) in data">
                                <div class="head">
                                    <img :src="item.image">
                                    <h2>{{item.name}}</h2>
                                    <p class="desc">{{item.desc}}</p>
                                </div>
                                <div class="condition-wrap">
                                    <div>
                                        <div class="condition-title">基础条件</div>
                                        <div class="condition-sub-title" v-if="unsatisfiedCount[item.type] > 0">你已满足 <b style=" color: #59BE65; ">{{item.conditions.length - unsatisfiedCount[item.type]}}</b> 个基本条件要求，还差 <b style=" color: #ea4359; ">{{unsatisfiedCount[item.type]}}</b> 个要求</div>
                                        <div class="condition-sub-title" v-else>你已满足我们认证的基本条件，开启认证之旅吧！</div>
                                    </div>
                                    <div class="condition-list">
                                        <div class="condition-item" v-for="(v,k) in item.conditions">
                                            <div class="item-inner bg" v-if="!v.allow && (v.key == 'money' || v.key == 'credit')">
                                                <i class="ri-money-cny-circle-fill"></i>
                                                <div style="flex:1;">
                                                    <span class="name">认证费用</span>
                                                    <p class="text-ellipsis">需要{{v.name}}</p>
                                                </div>
                                                <button class="bg-text" @click="pay(v,index)">去支付</button>
                                            </div>
                                            <div class="item-inner" v-else>
                                                <span class="name">{{v.name}}</span>
                                                <span class="check-icon">
                                                    <i class="ri-check-fill" v-if="v.allow"></i>
                                                    <i class="ri-close-fill" style="color: #EA4359;" v-else></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="pay-button" style="padding: 0px; margin-top: 36px;">
                                    <button style="height:44px;" :disabled="unsatisfiedCount[item.type] > 0" @click="onNext(item,index)">申请认证</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--第二步-->
                <div class="step-two-wrap box qk-radius" v-else-if="step == 2" v-cloak>
                    <div class="form-container">
                        <form>
                            <div class="form-item is-required">
                                <label class="label">认证称号</label>
                                <div class="input-box">
                                    <input type="text" autocomplete="off" maxlength="25" placeholder="请输入认证信息" class="input" v-model="formData.title">
                                    <p>认证信息最多25个字，审核通过后不可修改。<br>不能使用修饰性词汇，不能包含联系方式与推广信息。<br>能够清晰地表达你的身份、领域或品牌的简洁认证信息</p>
                                </div>
                            </div>
                            <!--企业机构-->
                            <div class="official" v-if="currentVerifyData.verify_info_types && currentVerifyData.verify_info_types.includes('official')">
                                <div class="form-item is-required">
                                    <label class="label">企业全称</label>
                                    <div class="input-box max-width">
                                        <input type="text" autocomplete="off" maxlength="100" placeholder="请输入认证信息" class="input" v-model="formData.company">
                                        <p>与组织机构代码证/营业执照名称一致。</p>
                                    </div>
                                </div>
                                <div class="form-item is-required">
                                    <label class="label">统一社会信用代码</label>
                                    <div class="input-box max-width">
                                        <input type="text" autocomplete="off" maxlength="100" placeholder="请输入信用代码" class="input" v-model="formData.credit_code">
                                        <p>与组织机构代码证/营业执照统一社会信用代码一致。</p>
                                    </div>
                                </div>
                                <div class="form-item is-required">
                                    <label class="label">营业执照</label>
                                    <div class="input-box">
                                        <label class="upload-wrap" for="license-upload-input" :style="'background-image:url('+formData.business_license+')'">
                                            <div v-if="!formData.business_license">
                                                <i class="ri-add-fill"></i>
                                                <span>营业执照</span>
                                            </div>
                                        </label>
                                        <p>上传资料要求：请上传最新版三证合一高清彩色版企业营业执照/事业单位法人证明，或加盖红色公章的复印件（非电子公章），请确保材料完整清晰，便于识别。<a class="active">查看示例</a></p>
                                        <input id="license-upload-input" type="file" ref="uploadInput" accept="image/jpg,image/jpeg,image/png,image/gif" style="display: none;" @change="handleUpload($event,'business_license')">
                                    </div>
                                </div>
                                <div class="form-item is-required">
                                    <label class="label">认证申请公函</label>
                                    <div class="input-box">
                                        <label class="upload-wrap" for="auth-upload-input" :style="'background-image:url('+formData.business_auth+')'">
                                            <div v-if="!formData.supplement">
                                                <i class="ri-add-fill"></i>
                                                <span>认证申请公函</span>
                                            </div>
                                        </label>
                                        <p>请下载官方模板<a href="//activity.hdslb.com/blackboard/static/20220815/4264715f749e9494cc2bd09303a11a7e/vaVeJB5BJr.docx" class="active">《机构认证申请公函》</a>，并加盖企业公章（合同章、财务章无效）和运营人手写签名后扫描或者拍照上传。<a class="active">查看示例</a></p>
                                        <input id="auth-upload-input" type="file" ref="uploadInput" accept="image/jpg,image/jpeg,image/png,image/gif" style="display: none;" @change="handleUpload($event,'business_auth')">
                                    </div>
                                </div>
                                <div class="form-item">
                                    <label class="label">官网地址</label>
                                    <div class="input-box max-width">
                                        <input type="text" autocomplete="off" maxlength="100" placeholder="请输入官网地址" class="input" v-model="formData.official_site">
                                    </div>
                                </div>
                                <div class="form-item">
                                    <label class="label">补充文件</label>
                                    <div class="input-box">
                                        <label class="upload-wrap" for="supplement-upload-input" :style="'background-image:url('+formData.supplement+')'">
                                            <div v-if="!formData.supplement">
                                                <i class="ri-add-fill"></i>
                                                <span>补充文件</span>
                                            </div>
                                        </label>
                                        <input id="supplement-upload-input" type="file" ref="uploadInput" accept="image/jpg,image/jpeg,image/png,image/gif" style="display: none;" @change="handleUpload($event,'supplement')">
                                    </div>
                                </div>
                            </div>
                            <!--个人-->
                            <div class="personal" v-if="currentVerifyData.verify_info_types && currentVerifyData.verify_info_types.includes('personal')">
                                <div class="form-item is-required">
                                    <label class="label">运营者姓名</label>
                                    <div class="input-box max-width">
                                        <input type="text" autocomplete="off" maxlength="100" placeholder="请输入运营者姓名" class="input" v-model="formData.operator">
                                        <p>账号实际运营者需和运营授权函上一致，并在授权函上手写签字。</p>
                                    </div>
                                </div>
                                <div class="form-item is-required">
                                    <label class="label">运营者身份证号</label>
                                    <div class="input-box max-width">
                                        <input type="text" autocomplete="off" maxlength="100" placeholder="请输入运营者身份证号" class="input" v-model="formData.id_card">
                                        <p>账号实际运营者需和运营授权函上一致，并在授权函上手写签字</p>
                                    </div>
                                </div>
                                <div class="form-item is-required">
                                    <label class="label">运营者人员身份证件</label>
                                    <div class="input-box max-width">
                                        <div>
                                            <label class="upload-wrap" for="front-upload-input" :style="'background-image:url('+(formData.idcard_front?formData.idcard_front:'<?php echo QK_THEME_URI.'/Assets/fontend/images/idcard-front.png'?>')+')'">
                                                <div v-if="!formData.idcard_front">
                                                    <i class="ri-add-fill"></i>
                                                    <span>身份证正面照</span>
                                                </div>
                                            </label>
                                            <label class="upload-wrap" for="verso-upload-input" :style="'background-image:url('+(formData.idcard_verso?formData.idcard_verso:'<?php echo QK_THEME_URI.'/Assets/fontend/images/idcard-verso.png'?>')+')'">
                                                <div v-if="!formData.idcard_verso">
                                                    <i class="ri-add-fill"></i>
                                                    <span>身份证反面照</span>
                                                </div>
                                            </label>
                                            <input id="front-upload-input" type="file" ref="uploadInput" accept="image/jpg,image/jpeg,image/png,image/gif" style="display: none;" @change="handleUpload($event,'idcard_front')">
                                            <input id="verso-upload-input" type="file" ref="uploadInput" accept="image/jpg,image/jpeg,image/png,image/gif" style="display: none;" @change="handleUpload($event,'idcard_verso')">
                                        </div>
                                        <P>本人身份证件：身份证或个人护照，彩色照片，个人信息清晰无遮挡。</P>
                                    </div>
                                </div>
                                <div class="form-item is-required">
                                    <label class="label">手持身份证照片</label>
                                    <div class="input-box">
                                        <label class="upload-wrap" for="hand-upload-input" :style="'background-image:url('+(formData.idcard_hand?formData.idcard_hand:'<?php echo QK_THEME_URI.'/Assets/fontend/images/idcard-hand.png'?>')+')'">
                                            <div v-if="!formData.idcard_hand">
                                                <i class="ri-add-fill"></i>
                                                <span>上传照片</span>
                                            </div>
                                        </label>
                                        <p>请提交运营者手持身份证照片。<br>要求本人、身份证在同一照片中，且照片放大能看清身份<br>证上的文字和身份证号码。</p>
                                        <input id="hand-upload-input" type="file" ref="uploadInput" accept="image/jpg,image/jpeg,image/png,image/gif" style="display: none;" @change="handleUpload($event,'idcard_hand')">
                                    </div>
                                </div>
                                <div class="form-item is-required">
                                    <label class="label">运营者手机号码</label>
                                    <div class="input-box max-width">
                                        <input type="text" autocomplete="off" maxlength="100" placeholder="请输入运营者手机号码" class="input" v-model="formData.telephone">
                                        <p>填写运营者正在使用的手机号码，认证审核过程中审核人员将通过该号码与你联系，请保持电话畅通。</p>
                                    </div>
                                </div>
                                <div class="form-item">
                                    <label class="label">运营者联系邮箱</label>
                                    <div class="input-box max-width">
                                        <input type="text" autocomplete="off" maxlength="100" placeholder="请输入运营者联系邮箱" class="input" v-model="formData.email">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="form-btn">
                        <button @click="submitVerify" :disabled="!protocol">申请认证</button>
                        <button class="bg-text" @click="onPrev">上一步</button>
                        <label style=" display: flex; grid-gap: 4px; ">
                            <input type="checkbox" v-model="protocol">我已同意 <a href="<?php echo $verify_page['agreement']?>" target="_blank" class="active">《官方认证服务协议》</a>
                        </label>
                    </div>
                </div>
                <!--第三步-->
                <div v-else-if="step == 3 || step == 4" class="step-last-wrap box qk-radius">
                    <div class="inner" v-if="status == 0 || status == 3">
                        <div class="icon" style=" color: #efdf4b; ">
                            <i class="ri-time-line"></i>
                        </div>
                        <h2>认证审核中</h2>
                        <p class="desc">我们将会对你的申请进行审核，7个工作日内会通过站内信将结果推送给你，请耐心等待</p>
                    </div>
                    <div class="inner"  v-if="status == 1">
                        <div class="icon">
                            <i class="ri-check-fill"></i>
                        </div>
                        <h2>认证成功</h2>
                        <p class="desc">恭喜认证成功，已为你添加认证V标识以及认证信息</p>
                    </div>
                    <div class="inner" v-if="status == 2">
                        <div class="icon" style=" color: #ff725c; ">
                            <i class="ri-close-line"></i>
                        </div>
                        <h2>认证未通过</h2>
                        <p class="desc">{{opinion}}</p>
                        <div class="button" @click="onPrev">修改信息</div>
                    </div>
                </div>
            </div>
            <div class="verify-footer">
                <h2>常见问题</h2>
                <div class="verify-faq">
                    <div class="collapse" data-accordion="true">
                        <?php foreach($verify_page['faqs'] as $v): ?>
                          <div class="collapse-item">
                            <div class="collapse-header"><span><?php echo $v['key'] ?></span><i class="ri-arrow-down-s-line"></i></div>
                            <div class="collapse-content">
                                <div class="text"><?php echo $v['value'] ?></div>
                            </div>
                          </div>
                          <?php endforeach ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<?php
get_footer();