<?php
//圈子发布编辑器
$user_id = get_current_user_id();
?>

<div class="circle-editor-wrapper box">
    <div class="circle-editor-simple">
        <?php echo 
            qk_get_avatar(array(
                'src'=>get_avatar_url( $user_id, 43),
                'alt'=>$user_id ? get_the_author_meta('display_name', $user_id) : '游客的头像'
            )); 
        ?>
        <div class="simple-text-input text-ellipsis" onclick="qkMomentEditor.show = !qkMomentEditor.show"> 记录、讨论、分享你的日常 </div>
        <div class="image">
            <i class="ri-camera-fill"></i>
        </div>
    </div>
    <div class="circle-editor" ref="circleEditor" v-cloak v-show="show">
        <div class="circle-editor-content">
            <div class="editor-title">
                <input v-model="title" :placeholder="'标题'+(privacy.type != 'none' ? '':'非')+'必填，最多 '+numberLimit.maxTitke+' 字'" :maxlength="numberLimit.maxTitke" class="editor-input" :class="[{required:privacy.type != 'none'}]" ref="momentTitle">
                <span class="editor-input-limit" v-cloak>{{numberLimit.titleLength}}/{{numberLimit.maxTitke}}</span>
            </div>
            <div class="editor-content">
                <textarea rows="4" v-model="content" :maxlength="numberLimit.maxContent" :minlength="numberLimit.minContent" placeholder="有什么新鲜事？" class="editor-textarea" ref="momentContent"></textarea>
            </div>
        </div>
        <div class="publish-tool-view" v-cloak>
            <div class="circle-image-container" v-show="toolType=='image' || image.list.length">
                <div class="image-list">
                    <div class="image-item" v-for="(item,index) in image.list">
                        <div class="img">
                            <img :src="item.url" width="80" height="80" class="w-h">
                        </div>
                        <div class="tool-view-close" @click="image.list.splice(index, 1);"><i class="ri-close-fill"></i></div>
                    </div>
                    <div class="image-item" @click.stop="showImgUploadBox" v-if="image.list.length < image.count">
                        <div class="upload-btn"><i class="ri-add-fill"></i></div>
                    </div>
                </div>
            </div>
            <div class="circle-video-container" v-if="video.list.length">
                <div class="video-upload-status" v-for="(item,index) in video.list">
                    <div class="upload-progress-bg">
                        <div class="progress-bg-active" :style="'width: ' + item.progress + '%;'"></div>
                    </div>
                    <div class="upload-detail">
                        <div class="video-icon"><i class="ri-file-video-fill"></i></div>
                        <div class="upload-progress">
                            <div class="upload-info">
                                <div class="left">
                                    <div class="video-name text-ellipsis">{{item.name}}</div>
                                    <div class="upload-status">{{item.progress}}%</div>
                                </div>
                                <div class="right" @click="$refs.videoInput.click()">
                                    <i class="ri-refresh-line"></i>
                                </div>
                            </div>
                            <div class="progress-wrap">
                                <div class="progress-inner" :style="'width: ' + item.progress + '%;'"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="video-cover-wrap">
                    <div class="video-cover">
                        <div class="cover-img qk-radius" @click="changeVideoThumb('')">
                            <transition name="fade">
                                <img :src="video.thumb.url" class="w-h" v-if="video.thumb">
                            </transition>
                            <div class="cover-upload-btn">
                                <span>自定义更改封面</span>
                            </div>
                        </div>
                    </div>
                    <div class="cover-preview">
                        <div class="cover-preview-desc text-ellipsis"> 系统默认选中第一帧为视频封面，以下为更多智能推荐封面</div>
                        <div class="cover-preview-list">
                            <div class="cover-preview-item" :class="[{selected:item.url === video.thumb.url}]" v-for="(item,index) in video.list[0].thumbList" >
                                <transition name="fade">
                                    <img class="preview-img w-h" :src="item.url" v-if="item.url" @click="changeVideoThumb(item)"/>
                                </transition>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tool-view-close">
                    <i class="ri-close-fill"></i>
                </div>
            </div>
            <div class="privacy-container" v-show="privacy.type != 'none'">
                <div class="hidden-textarea">
                    <div class="privacy-role">
                        <div class="pay">
                            <div class="bg-text"><i :class="privacy.list[privacy.type].icon"></i> {{privacy.list[privacy.type].text}}可见</div>
                            <input :placeholder="'(数字) 设置'+privacy.list[privacy.type].text" maxlength="20" class="editor-input" v-model="privacy.value" v-if="privacy.type == 'password' || privacy.type == 'money' || privacy.type == 'credit'">
                        </div>
                        <ul v-if="privacy.type == 'roles'">
                            <li v-for="(lv,key) in currentUser.roles" :key="key"><label><input type="checkbox" v-model="privacy.roles" :value="key"/><span v-text="lv"></span></label></li>
                        </ul>
                    </div>
                    <textarea maxlength="800" minlength="5" v-model="privacy.content" :placeholder="'请在这里输入想要'+privacy.list[privacy.type].text+'才可查看隐藏的内容'"></textarea>
                </div>
                <div class="tool-view-close" @click="privacytoolViewClose">
                    <i class="ri-close-fill"></i>
                </div>
            </div>
            <div class="tags-select-container" v-if="circle && circle.circle_tags.length">
                <div class="tags-select-list">
                    <div class="tag-item">板块</div>
                    <div class="tag-item" :class="[{'bg-text':momentTag == ''}]" @click="momentTag = ''">综合</div>
                    <div class="tag-item" :class="[{'bg-text':momentTag == item.name}]" v-for="(item,index) in circle.circle_tags" @click="momentTag = item.name" v-text="item.name"></div>
                </div>
            </div>
        </div>
        <div class="circle-editor-tools">
            <transition name="fade">
            <div class="editor-tool-list" v-show="editor.toolbar.length">
                <div class="editor-tool-item" v-for="(item,index) in editor.toolbar" :key="index" @click.stop="toolClick(item.tool)" :class="[{disabled:isToolDisabled(item.tool)}]">
                    <div class="circle-image tool-icon" v-if="(item.tool == 'circle_cat' && circle_id)" v-cloak>
                        <img :src="circle.icon" width="20" height="20" class="circle-image-face w-h">
                    </div>
                    <span class="tool-icon" v-else>
                        <i :class="privacy.list[privacy.type].icon" v-if="item.tool == 'privacy'"></i>
                        <i :class="item.icon" v-else></i>
                    </span>
                    <span class="tool-name" v-if="item.name_show" v-text="(item.tool == 'circle_cat' && circle_id)  ? circle.name : (item.tool == 'privacy' ? privacy.list[privacy.type].text :item.name)"></span>
                </div>
                <div class="btn submit-button" :class="[{disabled:submitdisabled}]" v-cloak>
                    <button class="publish" @click="submitMoment()">发布</button>
                </div>
                <input ref="videoInput" type="file" name="file" accept="video/*" @change="fileUpload($event)" style="display: none" />
            </div>
            </transition>
        </div>
        <div class="circle-editor-tool-view" v-cloak @click.stop="">
            <template v-show="toolType =='emoji' && showEmoji">
                <qk-emoji v-model="showEmoji" :target="'.myElement'" @emoji-click="handleClick" class="tool-view-item"></qk-emoji>
            </template>
            <div class="selector-container box tool-view-item" v-show="toolType=='topic' || toolType=='circle_cat'">
                <div class="selector-inner">
                    <div class="selector-search">
                        <div class="search-input">
                            <input class="input" v-model="searchText" type="text" autocomplete="off" maxlength="100" :placeholder="toolType=='topic' ? '搜索话题':'搜索社区圈子'" @input="handleInput" @compositionstart="composing = true" @compositionend="composing = false">
                            <span class="search-icon">
                                <i class="ri-search-2-line"></i>
                            </span>
                        </div>
                    </div>
                    <div class="scroll-tabs-wrapper" ref="scrollTab" v-if="selectorTabs.length && toolType == 'circle_cat' && !searchText">
                        <ul class="tabs-content">
                            <li class="tab-item" :class="[{active:index == selectorTabIndex}]" v-for="(item,index) in selectorTabs" @click="selectorTabChange(index)">
                                <span v-text="item.name"></span>
                            </li>
                        </ul>
                    </div>
                    <transition name="c-fade">
                        <div class="selector-list" v-show="selectorList.length">
                            <div class="list-item" v-for="(item,index) in selectorList" @click="selector(item)">
                                <div class="group-name" v-text="item.cat_name" v-if="item.cat_name"></div>
                                <div class="info-box">
                                    <div class="image">
                                        <i class="ri-hashtag" v-if="!item.icon"></i>
                                        <img :src="item.icon" width="40" height="40" class="w-h tw-rounded" v-else>
                                    </div>
                                    <div class="info">
                                        <div class="title" v-text="item.name"></div>
                                        <div v-if="item.id">
                                            <span v-if="item.user_count">{{item.user_count}}圈友</span>
                                            <span>{{item.post_count}}动态</span>
                                        </div>
                                        <div v-else>
                                            <span v-text="item.desc"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="selected" v-if="item.id == circle_id && toolType=='circle_cat'"><i class="ri-checkbox-circle-fill"></i></div>
                                <div class="create" v-if="!item.id" @click="create(item)"><button class="bg-text">创建</button></div>
                            </div>
                        </div>
                    </transition>
                    <div class="loading empty" v-if="!selectorList.length && loading"></div>
                    <template v-else-if="!selectorList.length && !loading">
                        <!--<div class="empty">-->
                        <!--    <img src="https://www.qkua.com/wp-content/themes/qkua/Assets/fontend/images/empty.svg" class="empty-img"> -->
                        <!--    <p class="empty-text">暂无内容</p>-->
                        <!--</div>            -->
                    </template>
                </div>
            </div>
            <div class="privacy-menu-container box tool-view-item" v-show="toolType=='privacy'">
                <ul class="privacy-menu">
                    <li class="menu-item" :class="[{active:privacy.type == index,disabled:currentUser.privacy_role && typeof currentUser.privacy_role[index] !== 'undefined' && !currentUser.privacy_role[index]}]" v-for="(item,index) in privacy.list" @click="privacy.type = index;toolType = ''">
                        <i :class="item.icon"></i>
                        <span v-text="item.text"></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>