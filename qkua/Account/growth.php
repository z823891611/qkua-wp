<?php
/**
 * 成长等级
 * */
?>
<div class="growth-page" ref="growthPage">
    <div class="growth-header qk-flex" v-cloak>
        <div class="user-level-card qk-flex box qk-radius">
            <div class="level-name"><span v-text="lv.name"></span></div>
            <div class="level-exp" v-if="lv">
                <div class="level-icon">
                    <img :src="lv.icon" class="w-h">
                </div>
                <div class="level-exp-progress-bar">
                    <div class="exp-progress-bar" :style="'width:'+lv.exp_ratio+'%;'"></div>
                </div>
                <div class="level-exp-info qk-flex">
                    <span v-text="lv.exp+ ' / ' +lv.next_lv_exp"></span>
                    <span>升级还需要{{lv.exp >=lv.next_lv_exp ? 0 :lv.next_lv_exp - lv.exp}}经验</span>
                </div>
                <div class="level-mark"><img :src="'https://img-static.mihoyo.com/levelMark/levelMark'+(parseInt(lv.lv)+1)+'.png'" class="w-h"> </div>
            </div>
        </div>
        <div class="user-level-points box qk-radius">
            <!--<div class="title">等级成长</div>-->
            <div id="swiper-scroll" class="levels-container qk-flex" v-if="lv_data.length">
                <div class="level-item carousel__slide" :class="[{'selected':index == lv.lv}]" v-for="(item,index) in lv_data">
                    <div class="level-exp">{{item.exp}}</div>
                    <div class="level-bar" :style="'height:'+75 * ((index + 1) / lv_data.length)+'px;'"></div>
                    <div class="level-icon">
                        <img :src="item.image" class="w-h">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="growth-content box qk-radius">
        <div id="tabs" class="tabs">
            <ul class="tabs-nav">
                <li class="active">经验任务</li>
                <li>经验记录</li>
                <div class="active-bar"></div>
            </ul>
            <div class="tabs-content">
                <div class="task-box" v-if="tasks" v-cloak>
                    <div class="task-section" v-for="(task,index) in tasks" v-if="index != 'newbie_task'">
                        <div class="title" v-if="index == 'newbie_task'">初次见面</div>
                        <div class="title" v-else-if="index == 'daily_task'">每日任务<span>每日可完成多次</span></div>
                        <div class="title" v-else>推荐任务</div>
                        <div class="task-list">
                            <div class="list-item" v-for="(item,i) in task">
                                <div class="item-left">
                                    <div class="task-icon" style="display:none">
                                        <img src="">
                                    </div>
                                    <div class="task-title">
                                        <div>{{item.name}}</div>
                                        <div class="task-prize">
                                            <div class="prize-item" v-for="(prize) in item.task_bonus">
                                                <img src="https://qhstaticssl.kujiale.com/newt/102315/image/png/1625220425721/680E417FFC36BAEF1F3CC7A4D3817A2F.png" v-if="prize.key == 'credit'"/>
                                                <img src="https://qhstaticssl.kujiale.com/newt/102315/image/png/1625220425623/B48597D88FC9BA3032CAB4C3CF688E2E.png" v-else-if="prize.key == 'exp'"/>
                                                <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDgiIGhlaWdodD0iNDgiIHZpZXdCb3g9IjAgMCA0OCA0OCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTIwLjMzMjUgNDQuNjQ5QzIyLjQyMDUgNDYuNDUwMyAyNS41Nzk0IDQ2LjQ1MDMgMjcuNjY3NSA0NC42NDlMNDYuMTY3MiAyOC42ODk3QzQ3Ljg2MjcgMjcuMjI3IDQ4LjQ0OTEgMjQuOTA4OCA0Ny42NDI1IDIyLjg1NzdMNDAuNzg1NCA1LjQyMjcxQzM5Ljk3NTIgMy4zNjI1IDM3LjkyNzkgMiAzNS42NDI2IDJIMTIuMzU3NEMxMC4wNzIxIDIgOC4wMjQ4NCAzLjM2MjUgNy4yMTQ1NyA1LjQyMjcxTDAuMzU3NTM5IDIyLjg1NzdDLTAuNDQ5MTIzIDI0LjkwODggMC4xMzcyOTggMjcuMjI3IDEuODMyODEgMjguNjg5N0wyMC4zMzI1IDQ0LjY0OVoiIGZpbGw9IiMzQjMwOEQiLz4KPHBhdGggZD0iTTMzIDIxLjI5MjZDMzMgMjAuNzM5IDMyLjYyNjkgMjAuMjU0MiAzMi4wOTE1IDIwLjExMzNMMTUgMTUuNjE0N1YyMi4xNTQ3QzE1IDIyLjcwODMgMTUuMzczMSAyMy4xOTI0IDE1LjkwODUgMjMuMzMzM0wzMyAyNy44MzE5QzMzIDI1LjIzOTQgMzMgMjMuNjU3OCAzMyAyMS4yOTI2WiIgZmlsbD0iI0RDQTA0NCIvPgo8cGF0aCBkPSJNMjUuNTE5NyA3LjE2NDE3QzI1LjkwNDUgNi45NDUyOCAyNi4zNzkyIDYuOTQ1MjggMjYuNzY0MSA3LjE2NDE3TDMyLjc2MTkgMTAuNTc1OEMzMy4wNzk0IDEwLjc1NjQgMzMuMDc5NCAxMS4yMDY0IDMyLjc2MTkgMTEuMzg3TDE1LjY2NjcgMjEuMTEwOUMxNS4zMDAxIDIxLjMxOTQgMTUuMDU4NyAyMS42ODQxIDE1LjAwOTQgMjIuMDkxMkMxNS4wMDM0IDIyLjA0NCAxNS4wMDAzIDIxLjk5NjEgMTUgMjEuOTQ3NlYxMy44NTAzQzE1IDEzLjQxNTUgMTUuMjM1OSAxMy4wMTM3IDE1LjYxOSAxMi43OTU3TDI1LjUxOTcgNy4xNjQxN1oiIGZpbGw9IiNGRkQxOTUiLz4KPHBhdGggZD0iTTIyLjQ4MDkgMzUuODM1NEMyMi4wOTI3IDM2LjA1NDkgMjEuNjE0MyAzNi4wNTQ5IDIxLjIyNjEgMzUuODM1NEwxNS4yNDEzIDMyLjQ1MjZDMTQuOTE5NiAzMi4yNzA3IDE0LjkxOTYgMzEuODE2MSAxNS4yNDEzIDMxLjYzNDJMMzIuMzI0MyAyMS45NzgyQzMyLjY5MjUgMjEuNzcwMSAzMi45MzYyIDIxLjQwNjYgMzIuOTg5MSAyMUMzMi45OTYzIDIxLjA1MzEgMzMgMjEuMTA3MSAzMyAyMS4xNjE3VjI5LjE4MDNDMzMgMjkuNjE5MiAzMi43NjA4IDMwLjAyNDggMzIuMzcyNiAzMC4yNDQzTDIyLjQ4MDkgMzUuODM1NFoiIGZpbGw9IiNGRkQxOTUiLz4KPC9zdmc+Cg==" v-else/>
                                                <span class="pioints" :class="prize.key">+{{prize.value}}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="item-right" v-if="parseInt(item.completed_count) >= parseInt(item.task_count)  || item.is_completed">
                                    <img src="//s1.hdslb.com/bfs/static/studio/creativecenter-platform/img/web_img_finsh.702189ac.png" style=" height: 45px; width: 45px; ">
                                </div>
                                <div class="item-right"  v-else>
                                    <div class="threshold"><i>{{item.completed_count}}</i>/{{item.task_count}}</div>
                                    <div class="threshold-bar">
                                        <div class="threshold-progress" :style="'width:'+(Math.min((item.completed_count/item.task_count).toFixed(4)*100, 100))+'%'"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="record-list" v-if="data.length" v-cloak>
                    <div class="record-item" v-for="(item,index) in data">
                        <div class="record-type">
                            <div class="record-title" v-text="item.type_text"></div>
                            <div class="record-value" :class="[item.record_type,{red:item.value < 0}]"><b v-text="item.value > 0 ?'+' + item.value : item.value"></b> 经验</div>
                        </div>
                        <div class="record-detail">
                            <div class="record-desc" v-text="item.content"></div>
                            <div class="record-date" v-text="item.date"></div>
                        </div>
                    </div>
                </div>
                <div class="loading empty qk-radius" v-else-if="!data.length && loading && !isDataEmpty"></div>
                <template v-else-if="!data.length && isDataEmpty">
                    <?php echo qk_get_empty('暂无记录','empty.svg'); ?>
                </template>
            </div>
            <template v-if="index == 1">
                <?php echo qk_ajax_pagenav( array( 'paged' => 1, 'pages' => 1 ), 'json', 'page','change'); ?>
            </template>
        </div>
    </div>
</div>