var qkAuthor = new Vue({
    el: '.author-header',
    data: {
        userId: qk_author.author_id,
        is_follow:false,
        is_self:false,
        locked:false,
        loaded:false
    },
    mounted() {
        this.$http.post(qk_rest_url+'checkFollow','user_id='+ this.userId).then(res=>{
            this.is_self = res.data.is_self
            this.is_follow = res.data.is_follow
            this.loaded = true
        })
    },
    methods: {
        
        saveCover(url, id) {
            this.$http.post(b2_rest_url + 'saveCover', 'url=' + url + '&id=' + id + '&user_id=' + this.userId).then(res=>{
                this.toast.close()
            }
            )
        },
        onFollow(){
            if(!qktoken) return this.$createModal('login')
            if(this.locked == true) return
            this.locked = true
            
            this.$http.post(qk_rest_url+'userFollow','user_id=' + this.userId).then(res=>{
                this.is_follow = !this.is_follow
                
                this.locked = false
                
                this.$message({ message: '操作成功' , type: 'success' });
            }).catch(err=>{
                this.locked = false
                this.$message({ message: err.response.data.message , type: 'error' });
            })
        },
        whisper(){
            if(!qktoken) return this.$createModal('login')
            window.location.href = window.location.origin + `/message?whisper=${this.userId}`;
        }
    },
    watch: {
        progress(val) {
            this.toast.$elem.firstChild.lastElementChild.innerText = 'Loading...(' + val + '%)';
        }
    }
})

//关注与粉丝
var followsPage = new Vue({
    el: '.follows-page',
    data: {
        data: [],
        //分页数据
        selector: '.list',
        api: '',
        param: {
            user_id: qk_author.author_id,
            size: 10
        },
        apis: ['getFansList', 'getFollowList'],
        isDataEmpty:false,
        loading: true,
        locked:false,
    },
    mounted() {
        if (!this.$refs.followsPage) return;
        let index = this.$refs.followsPage.getAttribute('data-index');
        this.api = this.apis[index];

        Tabs();

        // 监听选项卡切换事件
        window.addEventListener('tabChange', (event) => {
            // 根据索引设置 API 地址
            this.api = this.apis[event.detail.index];
            this.data = [];
            this.loading = true;
            this.isDataEmpty = false,
            // 加载数据
            this.loadData();
        });

        // 加载数据
        this.loadData();
    },
    methods: {
        // 加载数据
        loadData() {
            this.$nextTick(() => {
                this.$refs.jsonPageNav.load(1, '', true);
            });
        },
        // 接收子组件的传值
        change(data) {
            this.data = data.data;
            this.isDataEmpty = (this.data.length === 0);
            this.loading = !this.loading;
            this.$nextTick(() => {
                listFadein(document.querySelectorAll('.follows-page .relation-list > li'),10)
            });
            
        },
        onFollow(item){
            if(!qktoken) return this.$createModal('login')
            if(this.locked == true) return
            this.locked = true
                
            this.$http.post(qk_rest_url+'userFollow','user_id=' + item.id).then(res=>{
                item.is_follow = !item.is_follow
                
                this.locked = false
                
                this.$message({ message: '操作成功' , type: 'success' });
            }).catch(err=>{
                this.locked = false
                
                this.$message({ message: err.response.data.message , type: 'error' });
            })
        }
    }
});

//评论页面
var commentsPage = new Vue({
    el: '.comments-page',
    data: {
        data: [],
        //分页数据
        selector: '.list',
        api: 'getUserCommentList',
        param: {
            user_id: qk_author.author_id,
            size: 10
        },
        isDataEmpty:false,
        loading: true,
        locked:false,
    },
    mounted() {
        if (!this.$refs.commentsPage) return;

        // 加载数据
        this.loadData();
    },
    methods: {
        // 加载数据
        loadData() {
            this.$nextTick(() => {
                this.$refs.jsonPageNav.load(1, '', true);
            });
        },
        // 接收子组件的传值
        change(data) {
            //this.data = [];
            this.data.push(...data.data);
            this.isDataEmpty = (this.data.length === 0);
            this.loading = !this.loading;
            this.$nextTick(() => {
                //listFadein(document.querySelectorAll('.comments-page .comments-list > li'),10)
            });
            
        },
    }
});

//动态页面
var dynamicPage = new Vue({
    el: '.dynamic-page',
    data: {
        data: [],
        //分页数据
        selector: '.list',
        api: 'getUserDynamicList',
        param: {
            user_id: qk_author.author_id,
            size: 10
        },
        isDataEmpty:false,
        loading: true,
        locked:false,
    },
    mounted() {
        if (!this.$refs.dynamicPage) return;

        // 加载数据
        this.loadData();
    },
    methods: {
        // 加载数据
        loadData() {
            this.$nextTick(() => {
                this.$refs.jsonPageNav.load(1, '', true);
            });
        },
        // 接收子组件的传值
        change(data) {
            //this.data = [];
            this.data = this.transformList(data.data);
            this.isDataEmpty = (this.data.length === 0);
            this.loading = !this.loading;
            this.$nextTick(() => {
                //listFadein(document.querySelectorAll('.comments-page .comments-list > li'),10)
            });
            
        },
        transformList(oldList) {
            const newList = oldList.reduce((acc, cur, index) => {
                if ((index+1) % 2 === 0) {
                    acc.even.push(cur); // 将偶数放到新数组的 even 数组中
                } else {
                    acc.odd.push(cur); // 将奇数放到新数组的 odd 数组中
                }
                return acc;
            }, { odd: [], even: [] });
            
            newList.odd.sort((a, b) => parseInt(a.name) - parseInt(b.name));
            newList.even.sort((a, b) => parseInt(a.name) - parseInt(b.name));
            
            return [...newList.odd, ...newList.even]; // 将 odd 和 even 数组合并
        }
    }
});

//收藏页面
var favoritePage = new Vue({
    el: '.favorite-page',
    data: {
        data: '',
        //分页数据
        selector: '.favorite-page .qk-grid',
        api: 'getUserFavoritesList',
        param: {
            user_id: qk_author.author_id,
            size: 10,
            post_type:'post'
        },
        tabs:['post','shop','video'],
        isDataEmpty:false,
        loading: true,
        locked:false,
    },
    mounted() {
        if (!this.$refs.favoritePage) return;
        
        Tabs();

        // 监听选项卡切换事件
        window.addEventListener('tabChange', (event) => {
            // 根据索引设置 API 地址
            this.param.post_type = this.tabs[event.detail.index];
            this.data = '';
            this.loading = true;
            this.isDataEmpty = false,
            // 加载数据
            this.loadData();
        });
        
        // 加载数据
        this.loadData();
    },
    methods: {
        // 加载数据
        loadData() {
            this.$nextTick(() => {
                this.$refs.postsPageNav.load(1, '', true);
            });
        },
        // 接收子组件的传值
        change(data) {
            this.data = data.data;
            this.isDataEmpty = this.data == '';
            this.loading = !this.loading;
            this.$nextTick(() => {
                //listFadein(document.querySelectorAll('.comments-page .comments-list > li'),10)
            });
            
        }
    }
});