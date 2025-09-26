var securePage = new Vue({
    el: '.secure-page',
    data: {
        locked:false
    },
    mounted() {
        
    },
    methods: {
        BindType(type) {
            this.$createModal('binding',{
                size:356,
                loading:false,
                keepAlive:false,
                props:{
                    data:{type}
                },
            })
        },
        unBinding(type) {
            
            if(confirm('确认要解除账号绑定吗？')) {
            
                if(this.locked == true) return
                this.locked = true
                this.$http.post(qk_rest_url+'unBinding',{type:type}).then(res=>{
                    
                    this.locked = false
                    this.$message({message: '解除绑定成功',type: 'success'});
                    
                    //刷新当前页面
                    setTimeout(()=>{
                        qkCurrentPageReload()
                    }, 2000)
                    
                }).catch(err=>{
                    this.locked = false;
                    this.$message({message: err.response.data.message,type: 'warning'});
                })
            }
        },
        binding(type) {
            //设置来路地址
            qkSetCookie('qk_referer_url', window.location.href);
            
            this.$message({ message: '拉取数据中，请稍后...', type: 'success' });
            
            this.$https.post(qk_rest_url+'socialLogin',{type:type}).then(res=>{
                
                if(res.data.qrcode) {
                    
                }else{
                    if(res.data.url){
                        window.location.href = res.data.url;
                    }
                }
            }).catch(err=>{
                this.$message({ message: err.response.data.message, type: 'error' });
            })
        }
    }
})

var settingsPage = new Vue({
    el: '.settings-page',
    data: {
        data: {
            nickname: '',
            sex: '',
            description: '',
        },
        locked: false,
        avatar: '',
    },
    mounted() {
        if (!this.$refs.settingsPage) return;
        
        this.$https.post(qk_rest_url + 'getUserSettings').then( res => {
            this.data.nickname = res.data.display_name;
            this.data.sex = res.data.sex;
            this.data.description = res.data.description;
            this.avatar = res.data.avatar;
        })
    },
    methods: {
        handleAvatarUpload() {
            if (this.$refs.avatarInput.files.length <= 0) return;
            if (this.locked) return;
            this.locked = true;
            
            let file = this.$refs.avatarInput.files[0];
            this.avatar = URL.createObjectURL(file);
            let formData = new FormData()

            formData.append('file',file,file.name)
            formData.append("post_id", 1)
            formData.append("type", 'avatar')
            
            this.$http.post(qk_rest_url + 'fileUpload',formData).then(res=>{
                this.saveAvatar(res.data.url,res.data.id);
                this.locked = false;
                this.$refs.avatarInput.value = null;
            }).catch(err=>{
                let msg = err.response.data.message
                msg = msg ? msg : '上传失败，请重新上传';
                this.$message({ message: msg, type: 'warning' });
                this.locked = false;
                this.$refs.avatarInput.value = null;
            })
        },
        saveAvatar(url,id){
            this.$http.post(qk_rest_url+'saveAvatar','url='+url+'&id='+id).then(res=>{
                this.$message({message: res.data.msg,type: 'success'});
                qkCurrentPageReload();
            })
        },
        saveUserInfo() {
            if (this.locked) return;
            this.locked = true;
            this.$https.post(qk_rest_url + 'saveUserInfo', this.data).then((res) => {
                this.locked = false;
                this.$message({message: res.data.msg,type: 'success'});
                //刷新当前页面
                qkCurrentPageReload();
            }).catch((err) => {
                this.locked = false;
                this.$message({message: err.response.data.message,type: 'warning'});
            });
        },
    }
})

var lvPage = new Vue({
    el: '.growth-page',
    data: {
        lv:'',
        lv_data:[],
        tasks:'',
        isDataEmpty:false,
        loading: true,
        locked:false,
        data: [],
        //分页数据
        selector: '',
        api: 'getUserRecords',
        param: {
            size: 6,
            type:'exp'
        },
        index:0
    },
    mounted() {
        if (!this.$refs.growthPage) return;
        this.$https.post(qk_rest_url + 'getUserLvInfo').then(res=> {
            this.lv = res.data.lv;
            this.lv_data = res.data.lv_group;
            console.log(this.lv_data)
            this.$nextTick(()=>{
                swiperScroll();
                this.getLevelBarPercentage();
            })
        })
        
        Tabs();

        // 监听选项卡切换事件
        window.addEventListener('tabChange', (event) => {
            // 根据索引设置 API 地址
            
            this.data = [];
            this.tasks = '';
            this.loading = true;
            this.isDataEmpty = false;
            this.index = event.detail.index;
            // 加载数据
            if(event.detail.index == 1) {
                this.loadData();
            }else{
                this.getTaskData();
            }
        });
        
        // 加载数据
        this.getTaskData();
    },
    methods: {
        getLevelBarPercentage(index) {
            const maxExp = parseInt(this.lv_data[this.lv_data.length - 1].exp);
            let h = 75 * ((index + 1) / this.lv_data.length)
        },
        getTaskData(){
            this.$https.post(qk_rest_url + 'getTaskData',{
                user_id:0,
                key:'exp'
            }).then(res=> {
                this.tasks = res.data
                this.loading = false
                this.isDataEmpty = (this.tasks.length === 0)
            })
        },
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
        }
    }
})

//投稿管理
var articlePage = new Vue({
    el: '.post-page',
    data: {
        data: '',
        //分页数据
        selector: '',
        api: 'getUserPostList',
        param: {
            size: 6,
            post_type:'post'
        },
        tabs:['post','circle','video','shop'],
        isDataEmpty:false,
        loading: true,
        locked:false,
    },
    mounted() {
        if (!this.$refs.articlePage) return;
        
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
                this.$refs.jsonPageNav.load(1, '', true);
            });
        },
        // 接收子组件的传值
        change(data) {
            this.data = data.data;
            this.isDataEmpty = this.data == '';
            this.loading = !this.loading;
        }
    }
});

//订单管理
var orderPage = new Vue({
    el: '.order-page',
    data: {
        data: [],
        //分页数据
        selector: '',
        api: 'getUserOrders',
        param: {
            size: 6,
            state:6
        },
        tabs:[6,3,0],
        isDataEmpty:false,
        loading: true,
        locked:false,
    },
    mounted() {
        if (!this.$refs.orderPage) return;
        
        Tabs();

        // 监听选项卡切换事件
        window.addEventListener('tabChange', (event) => {
            // 根据索引设置 API 地址
            this.param.state = this.tabs[event.detail.index];
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
            this.data.push(...data.data);
            this.isDataEmpty = (this.data.length === 0);
            this.loading = !this.loading;
        },
        deleteOrder(order){
            if (this.locked) return;
            if (window.confirm('确定要删除该订单吗？')) {
                this.locked = true;
                this.$https.post(qk_rest_url + 'deleteOrder', order).then((res) => {
                    this.locked = false;
                    
                    // 从数组中移除相应的元素
                    const index = this.data.findIndex(item => item.order_id === order.order_id);
                    if (index !== -1) {
                        this.data.splice(index, 1);
                    }
                    
                    this.$message({message: '删除订单成功！',type: 'success'});
                    
                }).catch((err) => {
                    this.locked = false;
                    this.$message({message: err.response.data.message,type: 'warning'});
                });
            }
        },
        isWithin7Days(dateString) {
            // 将给定的时间字符串转换为Date对象
            const targetDate = new Date(dateString);
            
            // 获取当前时间
            const currentDate = new Date();
            
            // 计算两个时间之间的毫秒差值
            const timeDiff = targetDate.getTime() - currentDate.getTime();
            
            // 将毫秒差值转换为天数
            const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));
            
            // 判断天数差
            return daysDiff > -3;
        }
    }
});

//任务中心
var taskPage = new Vue({
    el: '.task-page',
    data: {
        tasks:'',
        isDataEmpty:false,
        loading: true,
        locked:false,
    },
    mounted() {
        if (!this.$refs.taskPage) return;
        this.getTaskData()
    },
    methods: {
        getTaskData(){
            this.$https.post(qk_rest_url + 'getTaskData').then(res=> {
                this.tasks = res.data
                this.loading = false
                this.isDataEmpty = (this.tasks.length === 0)
            })
        }
       
    }
});

//我的钱包页面
var assetsPage = new Vue({
    el: '.assets-page',
    data: {
        data: [],
        //分页数据
        selector: '',
        api: 'getUserRecords',
        param: {
            size: 6,
            type:'money'
        },
        tabs:['money','credit'],
        isDataEmpty:false,
        loading: true,
        locked:false,
    },
    mounted() {
        if (!this.$refs.assetsPage) return;
        
        Tabs();

        // 监听选项卡切换事件
        window.addEventListener('tabChange', (event) => {
            // 根据索引设置 API 地址
            this.param.type = this.tabs[event.detail.index];
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
            this.data.push(...data.data);
            this.isDataEmpty = (this.data.length === 0);
            this.loading = !this.loading;
        }
    }
});

//推广中心
var distributionPage = new Vue({
    el: '.distribution-page',
    data: {
        isDataEmpty:false,
        loading: true,
        locked:false,
        data:[],
        userList: [],
        orderList: [],
        //分页数据
        selector: '',
        param: {},
        api: '',
        apis: ['getUserRebateOrders', 'getUserPartner'],
        index:0
    },
    mounted() {
        if (!this.$refs.distributionPage) return;
        
        Tabs();

        // 监听选项卡切换事件
        window.addEventListener('tabChange', (event) => {
            // 根据索引设置 API 地址
            this.api = event.detail.index !== 0 ? this.apis[event.detail.index - 1] : 0;
            this.orderList = [];
            this.userList = [];
            this.data = [];
            this.loading = true;
            this.isDataEmpty = false;
            this.index = event.detail.index;
            // 加载数据
            if(event.detail.index !== 0) {
                this.loadData();
            }
        });
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
            
            if(this.index == 2){
                this.userList = data.data;
            }else{
                this.orderList = data.data;
            }
            
            this.data = data.data;
            
            this.isDataEmpty = (this.data.length === 0);
            this.loading = !this.loading;
        },
        copyText(text){
            this.$copyText(text);
        }
    }
})

//提现
Vue.component('withdrawal',{
    props:['type','money','ratio','limit'],
    template: `
    <div class="withdrawal-container">
        <div class="colorful-header qk-flex">
            <div class="title">提现申请</div>
        </div>
        <div class="content-wrap">
            <div class="text">可提现金额</div>
            <div class="money"><span class="unit">￥</span>{{money}}</div>
            <div class="form-container">
                <form @submit.stop.prevent="cashOut">
                    <label class="form-item">
                        <input type="number" autocomplete="off" v-model="amount" maxlength="9" placeholder="请输入提现金额" class="input">
                        <span class="icon"><i class="ri-money-cny-circle-line"></i></span>
                        <span class="limit bg-text">最低提现 {{limit}} 元</span>
                    </label>
                    <div class="form-item setting-row"><div class="left">提现至</div> <div class="right" @click="editQrcode"><span>编辑</span><i class="ri-arrow-right-s-line"></i> </div></div>
                    <div class="form-button">
                        <button>申请提现</button>
                    </div>
                </form>
            </div>
            <div class="text">提现时扣除 {{ratio}}% 的手续费，以实际到账为准</div>
        </div>
    </div>`,
    data() {
        return {
            locked:false,
            amount:'',
        };
    },
    mounted(){
    },
    methods: {
        cashOut() {
            if(!confirm('申请提现需后台人工处理，一般24小时，确认要提现吗？')) return;
            
            if(this.locked) return
            this.locked = true
            
            this.$http.post(qk_rest_url+'cashOut',{
                type:this.type,
                money:this.amount
            }).then(res=>{
                this.locked = false
                this.$message({ message: '申请提现成功，请耐心等待审核', type: 'success' });
                this.$emit('close-modal');
                
                //刷新当前页面
                setTimeout(()=>{
                    qkCurrentPageReload()
                }, 2000)
            }).catch(err=>{
                this.locked = false
                this.$message({ message: err.response.data.message, type: 'error' });
            })
        },
        editQrcode(){
            this.$createModal('qrcode',{
                loading:true,
                keepAlive:false,
            })
        },
        destroy(){
            Object.assign(this.$data, this.$options.data.bind(this)());
        }
    },
})

//二维码
Vue.component('qrcode',{
    template: `
    <div class="qrcode-container">
        <div class="colorful-header qk-flex">
            <div class="title">设置收款码</div>
        </div>
        <div class="content-wrap">
            <div class="weixin-qrcode">
                <div class="qrcode-img" @click="imgUploadBox('weixin')">
                    <img class="w-h" :src="data.weixin" v-if="data.weixin"></img>
                    <i class="ri-image-add-line" v-else></i>
                </div>
                <p>微信收款码</p>
            </div>
            <div class="alipay-qrcode">
                <div class="qrcode-img" @click="imgUploadBox('alipay')">
                    <img class="w-h" :src="data.alipay" v-if="data.alipay"></img>
                    <i class="ri-image-add-line" v-else></i>
                </div>
                <p>支付宝收款码</p>
            </div>
        </div>
    </div>`,
    data() {
        return {
            locked:false,
            data:{
                alipay:'',
                weixin:''
            },
        };
    },
    mounted(){
        this.$http.post(qk_rest_url+'getUserQrcode').then(res=>{
            this.data = res.data
            this.$nextTick(()=>{
                this.$emit('loadinged')
            })
        }).catch(err=>{
            this.$message({ message: err.response.data.message, type: 'error' });
        })
    },
    methods: {
        saveQrcode(type,url) {
            if(this.locked) return
            this.locked = true
            
            this.$http.post(qk_rest_url+'saveQrcode',{
                type,
                url
            }).then(res=>{
                this.locked = false;
                this.data[type] = url;
                this.$message({ message: '收款码设置成功', type: 'success' });
            }).catch(err=>{
                this.locked = false
                this.$message({ message: err.response.data.message, type: 'error' });
            })
        },
        imgUploadBox(imgtype) {
            this.$createModal('imageUploadBox',{
                size:400,
                keepAlive:false,
                props:{
                    data:{
                        showTabType:1,
                        maxPicked:1,
                        postType:'qrcode',
                        callback:(data,type) => {
                            this.saveQrcode(imgtype,data[0]?.url)
                        }
                    }
                
                }
                
            })
        },
        destroy(){
            Object.assign(this.$data, this.$options.data.bind(this)());
        }
    },
})
