<div class="task-page box qk-radius" ref="taskPage">
    <div class="task-box" v-if="tasks" v-cloak>
        <div class="task-section" v-for="(task,index) in tasks">
            <div class="section-title" v-if="index == 'newbie_task'">初次见面</div>
            <div class="section-title" v-else-if="index == 'daily_task'">每日任务<span>每日可完成多次</span></div>
            <div class="section-title" v-else>推荐任务</div>
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
    <div class="loading empty qk-radius box" v-else-if="!tasks && loading && !isDataEmpty"></div>
    <template v-else-if="!tasks && isDataEmpty">
        <?php echo qk_get_empty('暂无内容','empty.svg'); ?>
    </template>
</div>