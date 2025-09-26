<?php
/**
 * 消息页面
 */
get_header();
?>
<!--新粉丝，回复我的，收到的赞，系统通知，服务通知，钱包通知-->

<div class="qk-single-content" style=" height: 100%; ">
    <div id="message" class="content-area message-page" v-cloak>
        <div class="message-box qk-im box">
            <div class="sub-panel" v-if="((clientWidth < 768 && index === '' && data.length) || clientWidth > 768)">
                <div class="title">近期消息</div>
                <div class="chat-list">
                    <div class="chat-list-wrapper">
                        <div class="chat-item" :class="[{active:index === i}]" v-for="(item,i) in data" @click="indexChange(i)">
                            <div class="user-avatar">
                                <img :src="item.from.avatar" class="avatar-face w-h">
                                <b class="badge red" style=" top: -4px; border-radius: 8px; right: -4px; transform: scale(0.833333); " v-if="item.unread" v-text="item.unread"></b>
                            </div>
                            <div class="user-info">
                                <div class="name-box">
                                    <div class="user-name">
                                        <div class="name" v-text="item.from.name"></div>
                                    </div>
                                    <div class="time" v-html="item.date"></div>
                                </div>
                                <div class="last-word" v-html="item.content"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="main-panel" v-if="(clientWidth < 768 && index !== '') || clientWidth > 768">
                <div class="message-panel" v-if="index !== ''">
                    <div class="head">
                        <div class="back" style=" position: absolute; left: 12px; " @click="backClick"><i class="ri-arrow-left-s-line" style=" font-size: 20px; "></i></div>
                        <div class="title" v-html="data[index].from.name"></div>
                    </div>
                    <div class="message-list" v-if="data[index].type == 'chat' || data[index].type == 'circle' || data[index].type == 'vip' || data[index].type == 'distribution'" ref="messageList">
                        <div class="msg-more">
                            <div class="no-more" v-if="noMore && paged > 1">没有更多消息了～</div>
                            <div class="loader" v-if="locked"><i class="ri-loader-fill"></i></div>
                        </div>
                        <div class="message-list-content message-render" v-if="list.length">
                            <div class="msg-item" v-for="(item, index) in list" :key="index">
                                <div class="date-split-msg" v-if="item.showDate" v-html="item.date"></div>
                                <div :class="item.is_self ? 'msg-container-self' : 'msg-container-other'" v-if="!item.title">
                                    <div class="user-avatar">
                                        <img :src="item.from.avatar" class="avatar-face w-h">
                                    </div>
                                    <div class="msg-main">
                                        <div class="msg img-msg" v-if="item.mark.type == 'image'">
                                            <img class="image lazyload" :style="containerStyle(item.mark)" :data-src="item.mark.url"/>
                                        </div>
                                        <div class="msg text-msg" v-html="item.content" v-else-if="item.content"></div>
                                        <div class="new-msg badge red" v-if="!item.is_read && !item.is_self"></div>
                                        <div :class="['is-read',{no:!item.is_read}]" v-if="item.is_self">{{item.is_read ? '已' : '未'}}读</div>
                                    </div>
                                </div>
                                <div class="msg-notify" v-else>
                                    <div class="msg-notify-container">
                                        <div class="title" v-text="item.title"></div>
                                        <div class="content" v-html="item.content"></div>
                                        <div class="meta-list">
                                            <div class="item" v-for="(meta,i) in item.mark.meta" :key="index" v-if="item.mark.meta">
                                                <span v-text="meta.key"></span>
                                                <span v-text="meta.value"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="notice-list message-list" v-else ref="messageList">
                        <div class="notice-list-content"  v-if="list.length">
                            <div class="notice-item" v-for="(item, index) in list" :key="index">
                                <div class="notice box">
                                    <a :href="item.from.link" class="avatar-link" target="_blank" rel="noopener noreferrer">
                                        <div class="user-avatar" >
                                            <img :src="item.from.avatar" class="avatar-face w-h">
                                        </div>
                                    </a>
                                    <div class="notice-content">
                                        <div class="notice-user">
                                            <a :href="item.from.link" class="user-link" target="_blank">
                                                <span class="user-name" v-text="item.from.name"></span>
                                            </a>
                                        </div>
                                        <div class="notice-message" v-html="item.content"></div>
                                        <div class="notice-action">
                                            <span v-text="item.title"></span>
                                            <span class="notice-date" v-html="item.date"></span>
                                        </div>
                                    </div>
                                    <a :href="item.post.link" v-if="item.post.thumb">
                                        <img class="notice-image" :src="item.post.thumb">
                                    </a>
                                </div>
                                <div class="new-msg badge red" v-if="!item.is_read"></div>
                            </div>
                        </div>
                        <div class="msg-more">
                            <div class="no-more" v-if="noMore" v-cloak>没有更多消息了～</div>
                            <div class="loader" v-if="locked"><i class="ri-loader-fill"></i></div>
                        </div>
                    </div>
                    <div class="send-box" v-if="data[index].type == 'chat'">
                        <div class="chat-tool">
                            <label>
                                <input accept="image/jpg,image/jpeg,image/png,image/gif" type="file" @change="handleFileUpload($event)" style=" display: none;" :disabled="locked" ref="imageInput">
                                <i class="ri-image-add-line"></i>
                            </label>
                            <div>
                                <i class="ri-emotion-line" @click.stop="showEmoji = !showEmoji"></i>
                                <qk-emoji v-model="showEmoji" @emoji-click="handleClick"></qk-emoji>
                            </div>
                        </div>
                        <div class="input-box">
                            <textarea class="" placeholder="输入私信内容..." maxlength="500" v-model="messageContent" ref="textarea"></textarea>
                        </div>
                        <div class="send-btn">
                            <button title="enter 发送 shift + enter 换行" @click="sendMessage">发送</button>
                        </div>
                    </div>
                </div>
                <template v-else>
                    <?php echo qk_get_empty('快找小伙伴聊天吧 ( ゜- ゜)つロ','empty.svg'); ?>
                </template>
            </div>
        </div>
    </div>
</div>
<?
get_footer();