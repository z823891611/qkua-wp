var qkMomentEditor = new Vue({
    el:'.circle-editor',
    data() {
        return {
            circle_id: 0, //圈子id
            circle:null,
            default_circle_id: 0,
            moment_id:0,
            title:'',
            content:'',
            momentType:'', //帖子类型 比如投票等等
            momentTag:'',
            searchText:'', //搜索词
            toolType:'', //工具栏点击类型
            circleList:[],
            topicList:[],
            selectorTabs:[],
            selectorTabIndex:0,
            selectorList:[],
            scroll:'',
            showEmoji:false,
            locked:false,
            circleSelectorShow:false, //圈子话题选择
            loading:false,
            composing:false, //用于判断是否中文打字输入
            typingTimer: null,
            image: {
                allow: true, //是否允许添加图片
                list: [],
                count: 9,
            },
            video: {
                allow: true,
                list: [],
                thumb:{},
                count: 0,
            },
            privacy: {
                type:'none',
                value:'',
                roles:[],
                content:'',
                list:{
                    none:{
                        text:'公开',
                        icon:'ri-earth-line',
                    },
                    money:{
                        text:'付费',
                        icon:'ri-money-cny-circle-line',
                    },
                    credit:{
                        text:'积分',
                        icon:'ri-coin-line',
                    },
                    login:{
                        text:'登录',
                        icon:'ri-login-circle-line',
                    },
                    comment:{
                        text:'评论',
                        icon:'ri-chat-2-line',
                    },
                    password:{
                        text:'密码',
                        icon:'ri-lock-password-line',
                    },
                    // fans:{
                    //     text:'粉丝',
                    //     icon:'ri-group-line',
                    // },
                    roles:{
                        text:'限制等级',
                        icon:'ri-vip-diamond-line',
                    },
                }
            },
            editor:{ //编辑器功能
                media_size:null,
                toolbar:[]
            },
            currentUser:{
                media_role:null,
                type_role:null,
                privacy_role:null,
            },
            show:false
        }
    },
    computed:{
        defaultSelectorList() {
            return [{
                icon:false,
                name:this.searchText ? this.searchText:'创建新圈子',
                desc:this.toolType == 'topic' ? '创建自定义话题':'创建专属自己的圈子'
            }];
        },
        numberLimit() {
            return { //数量限制
                maxTitke:50,
                titleLength:this.title.length,
                minContent:5,
                maxContent:800,
                contentLength:this.content.length,
            }
        },
        submitdisabled() {
            if(this.locked) return true;
            if(this.numberLimit.contentLength < this.numberLimit.minContent || this.numberLimit.contentLength > this.numberLimit.maxContent) return true;
        },
    },
    mounted(){
        if(!this.$refs.circleEditor) return;
        
        if(typeof qk_circle !== 'undefined') {
            
            if(qk_circle.id !== '0') {
                this.circle_id = this.default_circle_id = qk_circle.id;
                this.circle = qk_circle;
            }
            this.show = typeof qk_circle.showEditor !== 'undefined' ? true : false
        }
        
        //自动高度
        autosize(this.$refs.momentContent);
        
        this.getUserCircleCapabilities();
        this.getEditMomentData();
    },
    methods:{
        //获取用户在当前圈子能力及编辑器设置
        getUserCircleCapabilities(){
            this.$http.post(qk_rest_url+'getUserCircleCapabilities','circle_id='+this.circle_id).then(res=>{
                this.editor = res.data.editor
                delete res.data.editor
                this.currentUser = res.data
            })
        },
        async submitMoment(){
            if(this.locked === true) return
            this.locked = true
            await this.videoThumbUpload();
            
            let { list, ...privacy } = this.privacy;
            
            let contentHidden = this.privacy.content.length > 0 ? '[content_hide]'+this.privacy.content+'[/content_hide]' : '';
            
            let data = {
                'moment_id':this.moment_id,
                'type':this.momentType,
                'circle_id':this.circle_id,
                'title':this.$refs.momentTitle.value,
                'content':this.$refs.momentContent.value + contentHidden,
                'tag':this.momentTag,
                'image':this.image.list,
                'video':this.video.list.map((value, index) => {
                    return {
                        id:value.id,
                        url:value.url,
                        thumb:this.video.thumb.url
                    };
                }),
                'privacy':privacy
            }

            this.$http.post(qk_rest_url+'insertMoment',data).then(res=>{
                //CircleList.data.unshift(res.data)
                this.$message({ message: res.data.msg, type: 'success' });
                this.restData()
                
                if(qkMomentList.$refs.momentList) {
                    qkMomentList.$refs.momentList.insertAdjacentHTML('afterbegin', res.data.data);
                    this.$nextTick(()=>{
                        lazyLoadInstance.update()
                        new Packery('.circle-moment-list.qk-waterfall')
                        qkMomentList.updateVideoPlayer()
                    })
                }
                this.locked = false
            }).catch(err=>{
                this.locked = false
                this.$message({ message: err.response.data.message, type: 'error' });
            })
        },
        restData(){
            let resetData = this.$options.data.bind(this)();//this.$options.data.call(this); // 获取组件的初始数据

            // 指定要重置的键元素
            let keysToReset = ['title', 'content', 'privacy', 'video', 'image', 'momentTag', 'momentType'];
            
            // 创建一个新的对象，包含要重置的键和对应的初始值
            let newData = {};
            keysToReset.forEach(key => {
                newData[key] = resetData[key];
            });
            
            Object.assign(this.$data, newData); // 将新的数据合并到组件的数据中
        },
        selectorTabChange(index) {
            this.selectorTabIndex = index;
            
            if(this.circleList[this.selectorTabIndex].list.length) {
                this.selectorList = this.circleList[this.selectorTabIndex].list;
            }else{
                this.selectorList = this.defaultSelectorList
            }
            
            this.TabscrollTo(index);
        },
        selector(item) {
            if(item.id) {
                if(this.toolType == 'topic') {
                    //this.$refs.momentContent.value += "#"+ item.name +"#";
                    this.content += "#"+ item.name +"#";
                    this.$refs.momentContent.focus();
                //圈子页面发表不允许选择圈子
                }else if(!this.default_circle_id){
                    if(item.in_circle || !item.is_join_circle_post) {
                        this.momentTag = ''
                        this.circle = item;
                        this.circle_id = item.id;
                    }else{
                        return this.$message({ message: `您还没加入该[${item.name}]，无法选中`, type: 'warning' });
                    }
                }
                
                this.toolType = ''
            }
            
        },
        //创建圈子或话题
        create(item){
            
            if(!this.currentUser.can_create_circle) return this.$message({ message: `您当前无权创建[${item.name}]`, type: 'warning' }); 
            
            if(this.toolType == 'topic'){
                this.$createModal('createTopic',{
                    loading:false,
                    keepAlive:false,
                    props:{
                        name:item.name // 传递给组件的props
                    }
                })
            }else{
                createDrawer({componentName:'circleManage',componentProps: {
                    type: 'create' // 传递给组件的props
                }})
            }
        },
        getSearchCircle() {
            if(!this.searchText.trim()) return;
            this.loading = true;
            this.$https.post(qk_rest_url+'getSearchCircle',{
                'keyword':this.searchText.trim(),
                'type': this.toolType
            }).then(res=>{
                if( this.searchText && res.data.length) {
                    this.selectorList = res.data
                }else{
                    this.selectorList = this.defaultSelectorList
                }
                
                this.loading = false;
            })
        },
        getHotCircles() {
            this.loading = true;
            this.$https.post(qk_rest_url+'getAllCircles').then(res=>{
                this.circleList = res.data.list;
                this.selectorTabs = res.data.cats;
                
                if(this.circleList[this.selectorTabIndex].list.length) {
                    this.selectorList = this.circleList[this.selectorTabIndex].list;
                }else{
                    this.selectorList = this.defaultSelectorList
                }
                
                this.loading = false;
                
                this.$nextTick(() => {
                    if(this.$refs.scrollTab) {
                        this.scroll = new BScroll(this.$refs.scrollTab, {
                            scrollX: true,
                            probeType: 3, // listening scroll event
                            click:true    
                        })
                    }
                });
            })
        },
        getTopics() {
            this.loading = true;
            this.$https.post(qk_rest_url+'getTopics').then(res=>{
                this.selectorList = this.topicList = res.data.list;
                
                this.loading = false;
                
            })
        },
        toolClick(type) {
            
            if(this.toolType == type || (this.default_circle_id && type == 'circle_cat')) {
                this.toolType = ''
                return;
            }
            
            this.toolType = type;
            //this.selectorTabs = [],
            this.selectorTabIndex = 0;
            this.selectorList = [];
            this.searchText = '';
            
            if(this.toolType != 'emoji') {
                this.showEmoji = false;
                if(this.toolType == 'circle_cat'){
                    if(!this.circleList.length) {
                        this.getHotCircles();
                    }else{
                        this.selectorList = this.circleList[this.selectorTabIndex].list;
                    }
                    
                    this.$nextTick(() => {
                        if(this.$refs.scrollTab) {
                            this.scroll = new BScroll(this.$refs.scrollTab, {
                                scrollX: true,
                                probeType: 3, // listening scroll event
                                click:true    
                            })
                        }
                    });
                }
                
                else if(this.toolType == 'topic') {
                    if(!this.topicList.length) {
                        this.getTopics();
                    }else{
                        this.selectorList = this.topicList;
                    }
                }
                else if(this.toolType == 'video') {
                    // 触发文件选择对话框
                    this.$refs.videoInput.click();
                }
            }else{
                this.showEmoji = !this.showEmoji
            }
            
            this.toolPosition(event);
        },
        isToolDisabled(tool) {
            const mergedRole = { ...this.currentUser.media_role, ...this.currentUser.type_role }; // 合并权限配置
            
             if (mergedRole[tool] !== undefined && mergedRole[tool] === false) {
                return true; // 如果权限存在且为false，则禁用工具
            } else {
                return false; // 其他情况不禁用工具
            }
        },
        handleInput() {
            if(this.searchText.trim()) {
                this.selectorList = [];
                this.loading = true;
            }
            // 如果不是中文输入状态，则调用getSuggestions方法
            if (!this.composing) {
                clearTimeout(this.typingTimer);
                this.typingTimer = setTimeout(() => {
                    this.getSearchCircle();
                }, 500);
            }
        },
        handleClick(emoji) {
            //this.$refs.momentContent.value += "["+ emoji.name +"]";
            this.content += "["+ emoji.name +"]";
            this.$refs.momentContent.focus()
        },
        showImgUploadBox () {
            this.$createModal('imageUploadBox',{size:400,props:{
                data:{
                    showTabType:1,
                    maxPicked:this.image.count,
                    postType:'circle',
                    callback:(data,type) => {
                        this.image.list.push(...data);
                    }
                }
                
            }})
        },
        changeVideoThumb(data) {
            if(!data) {
                this.$createModal('imageUploadBox',{size:400,props:{
                    data:{
                        showTabType:2,
                        maxPicked:1,
                        postType:'circle',
                        callback:(data,type) => {
                           data.length && (this.video.thumb = data[0]);
                        }
                    }
                    
                }})
            }else{
                this.video.thumb = data
            }
        },
        fileUpload(event) {
            if(this.locked == true) return
            this.locked = true
            
            for (let i = 0; i < event.target.files.length; i++) {
                let file = event.target.files[i]
                let url = URL.createObjectURL(file)
                
                if(file.size <= this.editor.media_size.video * 1024000) {
    
                    // 检查是否已经上传过
                    if(this.video.list.findIndex(item => item.name === file.name && item.size === file.size) !== -1) {
                        setTimeout(()=> {
                            this.$message({ message: `文件[${file.name}]已经上传过，请选择其他文件`, type: 'warning' });
                        }, 300);
    
                        this.locked = false
                        continue
                    }
                    this.video.list.splice(i,1,{
                        'id':'',
                        'url':url,
                        'name':file.name,
                        'thumbList':Array(4).fill(''),
                        'progress':0,
                        'success':false,
                        'size': file.size,
                    })
                    
                    this.video.thumb = '';
                    
                    getVideoCover(file, 4).then((thumbList)=>{
                        this.video.list[i].thumbList = thumbList.map((value, index) => {
                            return {
                                url:URL.createObjectURL(value),
                                file:value
                            };
                        });
                        this.video.thumb = this.video.list[i].thumbList[0]
                    });
                    
                    let formData = new FormData()
                    formData.append('file', event.target.files[i], event.target.files[i].name)
                    formData.append("post_id", 1)
                    formData.append("type", 'circle')

                    const config = {
                        onUploadProgress: (progress) => {
                            this.video.list[i].progress = Math.floor(progress.loaded/progress.total*100)
                        },
                    }
    
                    this.$http.post(qk_rest_url + 'fileUpload',formData,config).then(res=>{
    
                        this.video.list[i].url = res.data.url
                        this.video.list[i].id = res.data.id
                        this.video.list[i].success = true
                        this.locked = false
    
                    }).catch(err=>{
                        let msg = err.response.data
                        this.$message({ message: msg ? msg.message : '网络原因上传失败，请重新上传', type: 'warning' });
                        this.video.list.splice(i, 1);
                        this.locked = false
                    })
    
                }else {
                    setTimeout(()=> {
                        this.$message({ message: "文件[" + file.name + "]大小超过限制"+this.editor.media_size.video+"M，请重新选择", type: 'error' });
                        this.locked = false
                    }, 300);
                }
            }
    
            this.$refs.videoInput.value = "" // 清空选择的文件
        },
        videoThumbUpload(){
            
            if(!this.video.list.length || !this.video.thumb.file) return
            
            let videoImg = new FormData()
            let fimeName = this.video.list[0].url.substring(0, this.video.list[0].url.lastIndexOf('.'))+'.jpg';
            fimeName = fimeName.substring(fimeName.lastIndexOf('/')+1,fimeName.length)
            videoImg.append('file',this.video.thumb.file,fimeName)
            videoImg.append("post_id", 1)
            videoImg.append("set_poster", this.video.list[0].id)
            //videoImg.append("file_name", fimeName)
            videoImg.append("type", 'circle')

            this.$http.post(qk_rest_url+'fileUpload',videoImg).then(res=>{
                this.video.thumb = res.data
            })
        },
        getEditMomentData() {
            let post_id = qkGetQueryVariable('id')
            let topic = qkGetQueryVariable('topics')
            if(topic && !post_id){
                this.content += "#"+ topic.trim() +"#";
                this.$refs.momentContent.focus();
            }
            
            if(!this.circle || !this.circle.can_edit || !post_id) return
            
            this.$http.post(qk_rest_url + 'getEditMomentData', 'post_id=' + post_id).then(res=>{
                this.moment_id = res.data.id;
                this.title = res.data.title;
                this.content = res.data.content;
                this.momentType = res.data.type; //帖子类型 比如投票等等
                this.momentTag = res.data.tag;
                this.image.list = res.data.image;
                this.video.list = res.data.video;
                if(this.video.list.length){
                    this.video.thumb = res.data.video[0].thumbList[0]
                }
                this.privacy = {...this.privacy,...res.data.privacy};
                this.$nextTick(()=>{
                   autosize.update(this.$refs.momentContent);
                })
            }).catch(err=>{
                this.$message({ message: err.response.data.message, type: 'error' });
            })
        },
        privacytoolViewClose() {
            this.privacy.type = 'none';
            this.privacy.value = '';
            this.privacy.roles = [];
            this.privacy.content = '';
        },
        toolPosition(event) {
            this.$nextTick(()=>{
                // 获取父元素的位置和大小信息
                const parentRect = this.$el.getBoundingClientRect();
                //可以通过event.currentTarget来获取当前被点击的元素
                const targetRect = event.currentTarget.getBoundingClientRect();

                  // 计算组件应该显示的位置
                const left = targetRect.left - parentRect.left + window.pageXOffset;
                // 获取浏览器窗口的宽度和高度
                const windowWidth = window.innerWidth || document.documentElement.clientWidth;

                // 计算组件的边界
                const componentWidth = document.querySelector('.tool-view-item').offsetWidth;

                const maxLeft = windowWidth - componentWidth - 16; // 留有12px的间隙
                // 根据浏览器边界调整组件的位置
                const adjustedLeft = Math.min(Math.max(left, 12), maxLeft);
                  // 设置组件的位置
                //document.querySelector('.selector-container').style.left = adjustedLeft + 'px';
                
                const circleElements = document.querySelector('.circle-editor-tool-view').children;

                 Array.from(circleElements).forEach(item => {
                    if (item.style.display !== 'none') {
                        item.style.left = adjustedLeft + 'px';
                    }
                });
            })
        },
        TabscrollTo(index) {
            var dom = this.$refs.scrollTab.children[0]
    
            var clientWidth = this.$refs.scrollTab.clientWidth //父元素宽度scroll-tab-wrapper
            var offsetLeft =  dom.children[index].offsetLeft //li 距离左侧宽度
            var offsetWidth = dom.children[index].offsetWidth //li 当前元素宽度
            
            if(clientWidth > dom.clientWidth) return;
    
            var left = offsetLeft - clientWidth/2 + offsetWidth/2
            left = left < 0 ? 0 : left  //最左
    
            left = left + clientWidth >= dom.scrollWidth ? dom.scrollWidth - clientWidth : left //最右
            this.scroll.scrollTo(-left, 0, 300)
        },
    },
    watch: {
        // '$refs.momentContent.value': {
        //     handler(newVal) {
        //       // 检查是否包含旧的话题，并删除之的输入
        //           var regex = /#[^#]+#/g;
        //           var matches = inputValue.match(regex);
        //           if (matches && matches.length > 1) {
        //             var lastIndex = inputValue.lastIndexOf(matches[matches.length - 1]);
        //             inputBox.value = inputValue.substring(lastIndex);
        //           }
        //     },
        // },
        searchText() {
            this.searchText = this.searchText.trim()
            if(!this.searchText) {
                if(this.toolType == 'topic') {
                    this.selectorList = this.topicList
                }else if(this.toolType == 'circle_cat' && this.circleList.length){
                    this.selectorList = this.circleList[this.selectorTabIndex].list;
                }
            }
        }
        
    }
})

var qkCircleInfoTop = new Vue({
    el:'.circle-info-top',
    data:{
       circle_id:0,
       locked:false,
    },
    mounted(){
        if(typeof qk_circle !== 'undefined') {
            this.circle_id = qk_circle.id;
        }
    },
    methods:{
        seeInfo(){
            createDrawer({componentName:'circleManage'})
        },
        joinCircle(){
            if(!qktoken) return this.$createModal('login')
            createModal('joinCircle')
        }
    }
})

var qkMomentList = new Vue({
    el:'.circle-content-wrap',
    data:{
       //分页数据
        selector: '.circle-moment-list',
        api: 'getMomentList',
        param: {
            size: 10,
            circle_id:typeof qk_circle !== 'undefined' ?qk_circle.id:0
        },
        videos: [], // 存储视频元素
        currentVideoIndex: 0, // 当前播放的视频索引
        isScrolling:false, // 用于标记是否正在滚动
        videoPlayId:'',
        tabIndex:0,
        tabType:'all',
        isDataEmpty:false,
        loading: false,
        locked:false,
        orderby:'',
        orderbyList:{
            '':'默认排序',
            'date':'发布时间',
            'comment_date':'回复时间',
            'modified':'修改时间',
            'random':'随机排序',
            'comments':'评论数最多',
            'views':'浏览数最多',
            'like':'点赞数最多',
        },
        circleList:[],
        circles:[],
        circleTabs:[],
        circleTabIndex:'all',
        pageCount:0,
        list_opt:[],
        pckry:'',
        locked:false,
    },
    mounted(){
        if(!this.$refs.circleContentWrap) return;
        
        lazyLoadInstance.restoreAll();//将 DOM 恢复到其原始状态用于解决vue和懒加载冲突
        lazyLoadInstance.update();
        
        this.list_opt = qk_list_opt.opts
        this.tabIndex = qk_list_opt.tabIndex

        this.updateVideoPlayer();
        this.$nextTick(() => {
            if(this.list_opt[this.tabIndex].list_style_type == 'list-3' && qkClientWidth > 768){
                this.pckry = new Packery('.circle-moment-list.qk-waterfall')
            }
            imgFancybox('[data-fancybox]',{groupAll:false})
            Tabs();
            window.addEventListener('scroll', _debounce(this.handleVideoPlay,100));
        });
    },
    computed: {
        waterfallClass() {
            if (this.list_opt[this.tabIndex] && this.list_opt[this.tabIndex].list_style_type === 'list-3' && qkClientWidth > 768 && !this.$refs.momentList.classList.contains('qk-waterfall')) {
                return 'qk-waterfall';
            }else{
                return '';
            }
        },
        playType(){
            return this.list_opt[this.tabIndex].video_play_type;
        }
    },
    methods:{
        changeTab(index,type,orderby = false) {
           
            if (this.$refs.momentList.classList.contains('qk-waterfall') && this.list_opt[index].list_style_type != 'list-3') {
                this.$nextTick(() => {
                    this.$refs.momentList.className = 'circle-moment-list';
                });
            }
            
            if(index == this.param.index && type == this.tabType && !orderby) return;
            
            if(this.locked == true) return;
            this.locked = true
            
            this.param.index = index;
            this.tabType = type;
            this.tabIndex = index;
            
            this.$refs.jsonPageNav.pageCount = 1;
            this.pageCount = 1;
            this.$refs.momentList.innerHTML = '';

            if(this.tabType != 'circle') {
                this.loading = true;
                this.isDataEmpty = false;
            }
            
            if (this.tabType == 'circle')  {
                this.locked = false;
                !this.circles.length && this.getCircles();
                return;
            }
            this.$nextTick(() => {
                this.$refs.jsonPageNav.load(1, '', true);
            });
        },
        changeOrderby(type) {
            this.param.orderby = this.orderby = type
            this.changeTab(this.param.index,this.tabType,true)
        },
        listChange(data){
            
            this.isDataEmpty = (data.data.length === 0 && !this.$refs.momentList.children.length);
            this.loading = false;
            this.locked = false;
            
            data.data.forEach((newHtml) => {
              // 创建新的Vue实例并挂载到新的div元素上
                const newVueInstance = new Vue({
                    template: newHtml,
                    data:this.$data,
                    methods: {
                        mataClick:this.mataClick,
                        setNum:this.setNum,
                        report:this.report,
                        setMomentBest:this.setMomentBest,
                        setMomentSticky:this.setMomentSticky,
                        getMomentId:this.getMomentId,
                        deleteMoment:this.deleteMoment,
                        changeMomentStatus:this.changeMomentStatus,
                    }
                }).$mount();
                
                // 给newVueInstance的根元素添加class
                newVueInstance.$el.classList.add('is-visible');
                // 将新的Vue实例的根元素追加到moment-list中
                this.$refs.momentList.appendChild(newVueInstance.$el);
            });
            
            imgFancybox('[data-fancybox]',{groupAll:false})
            
            this.$nextTick(() => {
                //this.pckry && this.pckry.destroy(); //导致瀑布流加载滚回顶部
                
                if(this.list_opt[this.tabIndex].list_style_type == 'list-3'){
                    qkClientWidth > 768 && (this.pckry = new Packery('.circle-moment-list.qk-waterfall'))
                }else{
                    this.pckry && this.pckry.destroy(); //导致瀑布流加载滚回顶部
                }
                
                this.updateVideoPlayer()
            });

            if(this.pageCount == 1){
                this.$scrollTo('.circle-scroll-to', 0, {offset: -56});//easing: 'ease-in'
            }

            this.pageCount = 0;
        },
        getCircles() {
            this.loading = true;
            this.$https.post(qk_rest_url+'getAllCircles').then(res=>{
                this.circles = res.data.list;
                this.circleTabs = res.data.cats;
                this.circleList = res.data.list.slice()
                this.circleList.splice(0, 1);
                this.loading = false;
                // this.$nextTick(() => {
                //     if(this.$refs.scrollTab) {
                //         this.scroll = new BScroll(this.$refs.scrollTab, {
                //             scrollX: true,
                //             probeType: 3, // listening scroll event
                //             click:true    
                //         })
                //     }
                // });
            })
        },
        circleCatChange(index) {
            this.circleTabIndex = index;
            
            if(this.circleTabIndex == 'all'){
                // 创建一个新的数组副本
                this.circleList = this.circles.slice();
                // 删除新数组的第一个元素
                this.circleList.splice(0, 1);
                return
            }
            
            if(this.circles[this.circleTabIndex].list.length) {
                this.circleList = [this.circles[this.circleTabIndex]];
            }else{
                this.circleList = []
            }
            
            // this.TabscrollTo(index);
        },
        // 控制视频播放
        handleVideoPlay() {
            if(this.playType == 'none' || this.playType == 'mouseover') return
            
            if (this.videos.length > 0) {
                let closestDistance = Infinity;
                this.videos.forEach((video, index) => {
                    const rect = video.getBoundingClientRect();
                    const distanceToTop = Math.abs(rect.top);
                    const videoElement = video.querySelector('video');
                    if (distanceToTop < closestDistance && rect.top > (-rect.height / 3 + 100) && rect.top < window.innerHeight - rect.height) {
                        closestDistance = distanceToTop;
                        this.$nextTick(()=>{
                            if(videoElement.paused && this.playType == 'scroll' && this.list_opt[this.tabIndex].list_style_type != 'list-3'){
                                let promise = videoElement.play();
                                if (promise !== undefined) {
                                    promise.then(() => {
                                        // video can play
                                    }).catch(err => {
                                        // video cannot play
                                        // 将视频设置为静音
                                        videoElement.muted = true;
                                        // 重新尝试播放
                                        videoElement.play();
                                    })
                                }
                            }
                        })
                    } else {
                        this.$nextTick(()=>{
                            !videoElement.paused && videoElement.pause();
                        })
                    }
                });
            }
        },
        updateVideoPlayer() {
            if(this.playType == 'none') return
            
            if (this.$refs.momentList) {
                this.videos = this.$refs.momentList.querySelectorAll('.moment-video-wrap');
                if(!this.videos.length) return
                
                this.videos.forEach((item,index) => {
                    const video = item.querySelector('video');
                    const videoPoster = item.querySelector('.video-poster .video-play-btn');
                    if (!video.hasListener) {
                        video.id = item.getAttribute('video-id')
                        video.addEventListener('play', () => {
                            this.videoPlayId = video.id
                            this.pauseOtherVideos(video);
                            
                            //当前播放的视频索引
                            this.currentVideoIndex = index
                        });
                        
                        video.addEventListener('pause', () => {
                            if (!video.seeking && video.id == this.videoPlayId) {
                                this.videoPlayId = ''
                            }
                        });
                        
                        if(this.playType == 'mouseover'){
                            item.addEventListener('mouseenter', () => {
                                video.paused && video.play();
                            });
                    
                            item.addEventListener('mouseleave', () => {
                                !video.paused && video.pause();
                            });
                        }
                        
                        if(this.playType == 'mouseover' || this.playType == 'click'){
                            videoPoster.addEventListener('click', () => {
                                event.preventDefault();
                                event.stopPropagation(); // 阻止事件冒泡
                                video.paused && video.play();
                            });
                        }
                        
                        video.hasListener = true;
                    }
                });
            }
        },
        pauseOtherVideos(currentVideo) {
            this.videos.forEach(item => {
                const video = item.querySelector('video');
                if (video !== currentVideo && !video.paused) {
                    video.pause();
                }
            });
        },
        //点赞
        mataClick(type){
            if(!qktoken) return this.$createModal('login');
            
            if(type == 'comment') {
                var parentElement = event.target.closest('.moment-card-inner')
                let href = parentElement.querySelector('.moment-content > a').href;
                qkCurrentPageReload(href + '#comments')
                return 
            }

            let apis = {
                like:'postVote',
                collect:'userFavorites'
            }
            
            if(this.locked == true || !apis[type]) return;
            this.locked = true
            
            let post_id = this.getMomentId(event)
            let e = event.currentTarget // 当前元素
            
            apis[type] && this.$http.post(qk_rest_url + apis[type],'post_id='+post_id).then(res=>{
                this.setNum(res.data.count,e);
                this.locked = false;
                this.$message({ message: res.data.message , type: 'success' });
            }).catch(err => {
                this.locked = false;
                this.$message({ message: err.response.data.message , type: 'error' });
            })
        },
        setNum(num,e){
            if(e.classList.contains('active')){
                e.classList.remove("active");
            }else{
                e.classList.add("active");
            }
            e.setAttribute('num',num)
        },
        report(){ 
            console.log(this.getMomentId(event))
            if(!qktoken) return this.$createModal('login');
            this.$createModal("report",{
                props:{
                    post_id:this.getMomentId(event)
                }
            })
        },
        setMomentBest(){
            //closest方法来获取最接近的具有指定类的父元素。closest方法会向上遍历DOM树，直到找到符合条件的元素为止
            var parentElement = event.target.closest('.moment-card-inner')
            let h2 = parentElement.querySelector('h2');
            if(!h2) {
                h2 = parentElement.querySelector('.content');
            }
            
            let post_id = this.getMomentId(event)
            
            this.$http.post(qk_rest_url + 'setMomentBest','post_id='+post_id).then(res=>{
                
                if(res.data.type) {
                    let span = document.createElement('span');
                    span.className = 'moment-best';
                    h2.insertBefore(span, h2.firstChild);
                }else{
                    let span = h2.querySelector('.moment-best');
                    if (span) {
                        h2.removeChild(span);
                    }
                }
                
                this.$message({ message: res.data.message , type: 'success' });
            }).catch(err => {
                this.$message({ message: err.response.data.message , type: 'error' });
            })
        },
        setMomentSticky(){
            
            //closest方法来获取最接近的具有指定类的父元素。closest方法会向上遍历DOM树，直到找到符合条件的元素为止
            var parentElement = event.target.closest('.moment-card-inner')
            let h2 = parentElement.querySelector('h2');
            if(!h2) {
                h2 = parentElement.querySelector('.content');
            }
            
            let post_id = this.getMomentId(event)
            
            this.$http.post(qk_rest_url + 'setMomentSticky','post_id='+post_id).then(res=>{
                
                if(res.data.type) {
                    let span = document.createElement('span');
                    span.className = 'moment-sticky';
                    h2.insertBefore(span, h2.firstChild);
                }else{
                    let span = h2.querySelector('.moment-sticky');
                    if (span) {
                        h2.removeChild(span);
                    }
                }
                
                this.$message({ message: res.data.message , type: 'success' });
            }).catch(err => {
                this.$message({ message: err.response.data.message , type: 'error' });
            })
        },
        getMomentId(event) {
            var momentCardInnerElement = event.target.closest('.moment-card-inner');
            if (momentCardInnerElement) {
                var dataId = momentCardInnerElement.getAttribute('data-id');
                return dataId;
            } else {
                return null;
            }
        },
        deleteMoment(){
            if(!confirm('确定要删除这个帖子吗？')) return

            //closest方法来获取最接近的具有指定类的父元素。closest方法会向上遍历DOM树，直到找到符合条件的元素为止
            var parentElement = event.target.closest('.moment-item')
            
            let post_id = this.getMomentId(event)
            
            this.$http.post(qk_rest_url + 'deleteMoment','post_id='+post_id).then(res=>{
                this.$message({ message: res.data.message , type: 'success' });
                if (parentElement) {
                    parentElement.remove();
                }
            }).catch(err => {
                this.$message({ message: err.response.data.message , type: 'error' });
            })
        },
        changeMomentStatus(){
            var parentElement = event.target.closest('.moment-card-inner')
            let pending = parentElement.querySelector('span .pending');
            
            let post_id = this.getMomentId(event)
            
            this.$http.post(qk_rest_url + 'changeMomentStatus','post_id='+post_id).then(res=>{
                this.$message({ message: '审核成功' , type: 'success' });
                if (pending) {
                    pending.remove();
                }
            }).catch(err => {
                this.$message({ message: err.response.data.message , type: 'error' });
            })
        },
    }
})

Vue.component('circleManage',{
    props: ['type'],
    template:`
        <div class="circle-manage-container">
            <div class="manage-sidebar" v-if="!isCreate">
                <div class="circle-image">
                    <img :src="data.icon" width="48" height="48" class="w-h" v-if="data.icon">
                </div>
                <div class="line"></div>
                <div class="circle-sidebar-menu">
                    <ul class="menu-list">
                        <li class="menu-item" :class="[{active:tabIndex == index,disabled:item.disabled}]" v-for="(item,index) in tabs" @click="changeTab(index)" :key="index" v-if="!item.disabled">
                            <span class="menu-icon"><i :class="item.icon"></i></span>
                            <span v-text="item.name"></span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="manage-content">
                <div class="manage-content-head">
                    <span class="title" v-text="tabs[tabIndex].name"></span>
                </div>
                <div class="manage-content-wrap">
                    <!---基础--->
                    <div class="base-manage" v-show="tabIndex == ''">
                        <div class="base">
                            <div class="circle-cover" @click="imgUploadBox('cover')">
                                <div class="cover-img" style=" display: contents; " v-colak>
                                    <img :src="data.cover" class="w-h" v-if="data.cover">
                                    <i class="ri-landscape-fill" v-else></i>
                                </div>
                            </div>
                            <div class="circle-icon" @click="imgUploadBox('icon')">
                                <div class="icon-img" style=" display: contents; " v-colak>
                                    <img :src="data.icon" class="w-h" v-if="data.icon">
                                    <i class="ri-camera-fill" v-else></i>
                                </div>
                            </div>
                        </div>
                        <div class="form-container">
                            <form>
                                <label class="form-item">
                                    <input type="text" v-model="data.name" autocomplete="off" maxlength="100" placeholder="圈子名称" class="input" :disabled="!currentUser.is_admin && !currentUser.is_circle_staff && !isCreate">
                                    <span class="icon"><i class="ri-file-text-line"></i></span>
                                </label>
                                <label class="form-item">
                                    <textarea placeholder="圈子简介" v-model="data.desc" :disabled="!currentUser.is_admin && !currentUser.is_circle_staff && !isCreate"></textarea>
                                    <span class="icon"><i class="ri-file-text-line"></i></span>
                                </label>
                                <label class="form-item">
                                    <input type="text" autocomplete="off" maxlength="100" v-model="data.slug" placeholder="圈子连接" class="input" :disabled="!currentUser.is_admin && !currentUser.is_circle_staff && !isCreate">
                                    <span class="icon"><i class="ri-link"></i></span>
                                    <p class="desc">链接：{{window.location.origin}}/circle/{{data.slug}}</p>
                                </label>
                            </form>
                            <div class="circle-cats">
                                <div class="section-title">圈子分类</div>
                                <ul class="circle-cats-list">
                                    <li class="list-item" :class="[{'bg-text':item.name == data.circle_cat,'disabled':!currentUser.is_admin && !currentUser.is_circle_staff && !isCreate}]" v-for="(item,index) in circle_cats" :key="index" v-text="item.name" @click="data.circle_cat = item.name"></li>
                                </ul>
                            </div>
                        </div>
                        <div class="nocan-info" v-if="isCreate && !currentUser.can_create_circle">
                            <div class="text-center">
                                <i class="ri-shield-user-fill"></i>
                                <p>当前权限不足，无法创建圈子</p>
                            </div>
                        </div>
                    </div>
                    <!---用户管理---->
                    <qk-circle-user-manage :currentUser="currentUser" v-if="tabIndex == 'user'"></qk-circle-user-manage>
                    <!---帖子管理---->
                    <qk-circle-post-manage :currentUser="currentUser" v-if="tabIndex == 'post'"></qk-circle-post-manage>
                    <!---布局---->
                    <div class="circle-layout" v-show="tabIndex == 'layout'">
                        <div class="setting-row">
                            <div class="left">顶部信息显示</div>
                            <div class="right">
                                <div class="select">
                                    <span v-text="layoutOptions[setting.layout.info_show]"></span>
                                    <i class="ri-arrow-right-s-line"></i>
                                </div>
                                <ul class="select-options">
                                    <li class="item" :class="[{active:index == setting.layout.info_show}]" v-for="(item,index) in layoutOptions" v-text="item" :key="index" @click="setting.layout.info_show = index"></li>
                                </ul>
                            </div>
                        </div>
                        <div class="setting-row">
                            <div class="left">帖子发布框显示</div>
                            <div class="right">
                                <div class="select">
                                    <span v-text="layoutOptions[setting.layout.editor_show]"></span>
                                    <i class="ri-arrow-right-s-line"></i>
                                </div>
                                <ul class="select-options">
                                    <li class="item" :class="[{active:index == setting.layout.editor_show}]" v-for="(item,index) in layoutOptions" v-text="item" :key="index" @click="setting.layout.editor_show = index"></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <!---权限---->
                    <div class="circle-role" v-show="tabIndex == 'role'">
                        <div class="setting-row">
                            <div class="left">加入圈子才能发帖</div>
                            <div class="right">
                                <div class="select">
                                    <span v-text="roleOptions[setting.role.join_post]"></span>
                                    <i class="ri-arrow-right-s-line"></i>
                                </div>
                                <ul class="select-options">
                                    <li class="item" :class="[{active:index == setting.role.join_post}]" v-for="(item,index) in roleOptions" v-text="item" :key="index" @click="setting.role.join_post = index"></li>
                                </ul>
                            </div>
                        </div>
                        <!--<div class="setting-row">
                            <div class="left">允许发帖</div>
                            <div class="right">
                                <label class="switch"><input type="checkbox">
                                    <span class="slider-dot"></span>
                                </label>
                            </div>
                        </div>-->
                    </div>
                    <!---隐私管理---->
                    <div class="circle-privacy" v-show="tabIndex == 'privacy' || (isCreate && tabIndex == '')">
                        <div class="setting-row">
                            <div class="left">帖子公开显示</div>
                            <div class="right">
                                <label class="switch">
                                    <input type="checkbox" v-model="setting.privacy.privacy">
                                    <span class="slider-dot"></span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="setting-row">
                            <div class="left">圈子类型</div>
                            <div class="right">
                                <div class="select">
                                    <span v-text="privacyTypeOptions[setting.privacy.type]"></span>
                                    <i class="ri-arrow-right-s-line"></i>
                                </div>
                                <ul class="select-options">
                                    <li class="item" :class="[{active:index == setting.privacy.type}]" v-for="(item,index) in privacyTypeOptions" v-text="item" :key="index" @click="setting.privacy.type = index"></li>
                                </ul>
                            </div>
                        </div>
                        <div class="setting-row" v-if="setting.privacy.type == 'password'">
                            <div class="left">圈子密码</div>
                            <div class="right">
                                <input type="text" autocomplete="off" v-model="setting.privacy.password" maxlength="4" placeholder="圈子密码（纯数字）" class="input" style=" background: var(--bg-muted-color); border-radius: var(--radius); max-width: 100px; padding: 5px 10px; ">
                            </div>
                        </div>
                        <div class="setting-roles" v-if="setting.privacy.type == 'roles'">
                            <div class="title">请选择专属等级</div>
                            <ul>
                                <li v-for="(lv,key) in roles" :key="key"><label><input type="checkbox" v-model="setting.privacy.roles" :value="key"/><span v-text="lv"></span></label></li>
                            </ul>
                        </div>
                        <div class="setting-pay-group" v-if="setting.privacy.type == 'money' || setting.privacy.type == 'credit'">
                            <div class="title">设置付费组</div>
                            <div class="pay-group-header">
                                <div class="name">名称<b class="red">*</b></div>
                                <div class="price">入圈价格(元/积分)<b class="red">*</b></div>
                                <div class="time">有效期(天)<b class="red">*</b></div>
                                <div class="discount">会员折扣(%)<b class="red">*</b></div>
                            </div>
                            <div class="pay-group-body" v-for="(item,index) in setting.privacy.pay_group">
                                <div class="name"><input type="text" v-model="item.name" @input="handleInput" placeholder="例：一个月"/></div>
                                <div class="price"><input type="number" v-model="item.price" step="1" maxlength="4" @input="handleInput" placeholder="例：20"/></div>
                                <div class="time"><input type="number" v-model="item.time" step="1" maxlength="4" @input="handleInput" placeholder="例：30"/></div>
                                <div class="discount">
                                    <input type="number" v-model="item.discount" step="1" maxlength="4" @input="handleInput" placeholder="例：97"/>
                                    <button v-if="setting.privacy.pay_group.length > 1" @click="deletePayGroup(index)">删除</button>
                                </div>
                            </div>
                            <div class="add-pay-group"><button @click="addPayGroup">添加</button></div>
                            <div class="pay-group-matter">
                                <ol>
                                    <li>名称：入圈支付时显示，比如 月付、季付、年付、永久有效 等等</li>
                                    <li>价格：入圈支付的金额或积分</li>
                                    <li>有效期：用户入圈有效天数，如果填写0则永久有效</li>
                                    <li>会员折扣：如果用户是会员使用折扣价</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                    <!---保存---->
                    <div class="submit-button" v-if="tabIndex != 'post' && tabIndex != 'user' && ( currentUser.is_admin || currentUser.is_circle_staff || (currentUser.can_create_circle && isCreate))">
                        <button class="publish" @click="createCircle" v-text="isCreate ? '创建':'保存'"></button>
                    </div>
                </div>
            </div>
        </div>
    `,
    data() {
        return {
            tabIndex:'',
            tabs:{
                '':{name:'基础信息',icon:'ri-donut-chart-line',disabled:false},
                user:{name:'用户成员',icon:'ri-group-line',disabled:true},
                post:{name:'帖子管理',icon:'ri-article-line',disabled:true},
                layout:{name:'布局设置',icon:'ri-layout-line',disabled:true},
                role:{name:'权限设置',icon:'ri-shield-user-line',disabled:true},
                privacy:{name:'隐私设置',icon:'ri-spy-line',disabled:true},
            },
            circle_cats:[],
            data:{
                id:0,
                name:'',
                desc:'',
                icon:'',
                cover:'',
                slug:'',
                circle_cat:'',
                original_cover: '',
                original_icon: '',
            },
            setting:{
                privacy:{
                    type:'free',
                    password:'',
                    roles:[],
                    pay_group:[{
                        name:'',
                        price:'',
                        time:'',
                        discount:''
                    }],
                    privacy:'public'
                },
                role:{
                    join_post:'global'
                },
                layout:{
                    info_show:'global',
                    editor_show:'global',
                }
            },
            currentUser:{
                can_create_circle: false,
                is_admin: false,
                is_circle_admin:false,
                is_circle_staff: false,
            },
            privacyTypeOptions:{
                free:'免费',
                money:'付费',
                credit:'积分',
                roles:'专属',
                password:'密码'
            },
            layoutOptions:{
                global:'使用系统设置',
                0:'关闭',
                pc:'pc端',
                mobile:'移动端',
                all:'pc端和移动端都显示'
            },
            roleOptions:{
                global:'使用系统设置',
                0:'关闭',
                1:'开启',
            },
            roles:[],
            locked:false,
        }
    },
    mounted() {
        if(!qktoken && this.isCreate) return this.$createModal('login');
        
        if(typeof qk_circle !== 'undefined' && !this.isCreate) {
            this.data.id = qk_circle.id;
        }
        
        this.getUserCircleCapabilities()
        
    },
    computed: {
        isCreate(){
            return this.type === 'create'
        }
    },
    methods: {
        //获取用户在当前圈子能力及编辑器设置
        getUserCircleCapabilities(){
            this.$http.post(qk_rest_url+'getUserCircleCapabilities','circle_id='+this.data.id).then(res=>{
                delete res.data.editor
                for (let key in res.data) {
                    if (this.currentUser.hasOwnProperty(key)) {
                        this.currentUser[key] = res.data[key];
                    }
                }
                
                this.roles = res.data.roles
                
                if(!this.isCreate) {
                    this.getCircleData()
                    if(this.currentUser.is_admin || this.currentUser.is_circle_admin){
                        this.getManageCircle()
                    }
                }
                
                
                this.getCircleCats()
                
                this.updateTabs()
            })
        },
        getCircleData(){
            this.$http.post(qk_rest_url+'getCircleData','circle_id='+this.data.id).then(res=>{
                for (let key in res.data) {
                    if (this.data.hasOwnProperty(key)) {
                        this.data[key] = res.data[key];
                    }
                }
            })
        },
        getCircleCats(){
            this.$http.post(qk_rest_url+'getCircleCats').then(res=>{
                this.circle_cats = res.data
            }).catch(err=>{
                this.$message({ message: err.response.data.message, type: 'error' });
            })
        },
        getManageCircle(){
            this.$http.post(qk_rest_url+'getManageCircle','circle_id='+this.data.id).then(res=>{
                this.setting = res.data
            }).catch(err=>{
                this.$message({ message: err.response.data.message, type: 'error' });
            })
        },
        createCircle() {
            if(this.locked == true) return
            this.locked = true
            
            let params = {
                ...this.data,
                ...this.setting
            }

            params.cover = this.data.original_cover ? this.data.original_cover : params.cover;
            params.icon = this.data.original_icon ? this.data.original_icon : params.icon;
            
            this.$http.post(qk_rest_url+'createCircle',params).then(res=>{
                this.locked = false
                this.$message({ message: '成功', type: 'success' });
                this.closeDrawer()
            }).catch(err=>{
                this.locked = false
                this.$message({ message: err.response.data.message, type: 'error' });
            })
        },
        updateTabs() {
            const isAdminOrCircleStaff = this.currentUser.is_admin || this.currentUser.is_circle_staff;
            const isAdminOrCircleAdmin = this.currentUser.is_admin || this.currentUser.is_circle_admin;
            
            this.tabs.user.disabled = this.isCreate;
            this.tabs.post.disabled = !isAdminOrCircleStaff || this.isCreate;
            this.tabs.layout.disabled = !isAdminOrCircleAdmin || this.isCreate;
            this.tabs.role.disabled = !isAdminOrCircleAdmin || this.isCreate;
            this.tabs.privacy.disabled = !isAdminOrCircleAdmin || this.isCreate;
        },
        changeTab(index) {
            if(index == this.tabIndex) return
            this.tabIndex = index
        },
        imgUploadBox(imgtype) {
            if(!this.currentUser.is_admin && !this.currentUser.is_circle_staff && !(this.currentUser.can_create_circle && this.isCreate)) return
            this.$createModal('imageUploadBox',{size:400,props:{
                data:{
                    showTabType:1,
                    maxPicked:1,
                    postType:'circle',
                    callback:(data,type) => {
                        if(imgtype == 'icon') {
                            this.data.icon = data[0].url
                            this.data.original_icon = ''
                        }else{
                            this.data.cover = data[0].url
                            this.data.original_cover = ''
                        }
                    }
                }
                
            }})
        },
        addPayGroup(){
            this.setting.privacy.pay_group.push({
                name:'',
                price:'',
                time:'',
                discount:''
            })
        },
        deletePayGroup(index){
            if(confirm('确定删除吗?')){
                this.setting.privacy.pay_group.splice(index,1)
            }
        },
        handleInput() {
            const inputValue = event.target.value = event.target.value.trim();
        },
        closeDrawer() {
            this.$emit('close-drawer');
        }
    },
    watch: {
        
    }
})

//圈主用户管理组件
Vue.component('qk-circle-user-manage',{
    props: ['currentUser'],
    template: `
    <div class="user-manage form-container">
        <form>
            <label class="form-item">
                <input class="input" v-model="searchText" type="text" autocomplete="off" maxlength="100" placeholder="搜索用户" @input="handleInput" @compositionstart="composing = true" @compositionend="composing = false">
                <span class="icon"><i class="ri-search-2-line"></i></span>
                <span class="invite"><i class="ri-user-add-line"></i></span>
            </label>
        </form>
        <div class="user-tabs" v-show="!searchText">
            <ul class="tabs-nav">
                <li :class="[{active:param.type == 'staff'}]" @click="changeuserTab('staff')">管理</li>
                <li :class="[{active:param.type == 'user'}]" @click="changeuserTab('user')">圈友</li>
            </ul>
        </div>
        <ul class="user-list scroll" v-if="userList.length">
            <li class="list-item is-visible" v-for="(item,index) in userList" :key="index">
                <a :href="item.link" v-html="item.avatar_html"></a>
                <div class="user-info">
                    <div class="user-info-name">
                        <a target="_blank" class="user-name no-hover" :href="item.link" v-text="item.name"></a>
                        <span class="user-lv" v-if="item.lv.icon">
                            <img :src="item.lv.icon" class="lv-img-icon">
                        </span>
                        <span class="user-vip" v-if="item.vip.image">
                            <img :src="item.vip.image" class="vip-img-icon">
                        </span>
                        <div class="user-circle-role" v-if="item.role == 'admin'">圈主</div>
                        <div class="user-circle-role staff" v-if="item.role == 'staff'">版主</div>
                    </div>
                    <div class="desc text-ellipsis" v-text="item.date ? item.date : item.desc"></div>
                </div>
                <div class="user-action" v-if="(currentUser.is_circle_staff && (item.role == 'member' || !item.role)) || currentUser.is_admin || currentUser.is_circle_admin">
                    <span class="button bg-text"  @click="removeCircleUser(item.id,index)" v-if="item.in_circle">移除</span>
                    <span class="button bg-text" @click="inviteUserJoinCircle(item.id,index)" v-else>邀请</span>
                    <span class="button bg-text" @click="setUserCircleStaff(item.id,index)" v-if="(item.role == 'member' || !item.role) && (currentUser.is_admin || currentUser.is_circle_admin)">设为版主</span>
                </div>
            </li>
        </ul>
        <div class="loading empty" v-else-if="loading && !userList.length" v-cloak></div>
        <template v-else-if="isDataEmpty">
            <div class="empty">
                <img src="/wp-content/themes/qkua/Assets/fontend/images/empty.svg" class="empty-img">
                <p class="empty-text">暂无数据</p>
            </div>
        </template>
        <div class="qk-pagenav json-nav">
            <page-nav ref="jsonPageNav" paged="1" pages="1" navtype="json" type="page" :api="'getCircleUsers'" :param="param" @change="changeUsers" v-once></page-nav>
        </div>
    </div>
    `,
    data() {
        return {
            param: {
                size: 10,
                circle_id:0,
                type:'staff'
            },
            circle_id:0,
            users:[],
            userList:[],
            searchText:'',
            composing:false,
            typingTimer: null,
            loading:false,
            isDataEmpty:false
        }
    },
    mounted() {
        
        if(typeof qk_circle !== 'undefined') {
            this.circle_id = this.param.circle_id = qk_circle.id;
            this.param.circle_id
        }
        
        !this.userList.length && this.$refs.jsonPageNav.load(1, '', true);
    },
    methods: {
        changeUsers(data){
            this.userList = this.users = [];
            this.userList = this.users = data.list
            this.isDataEmpty = !this.userList.length
        },
        changeuserTab(type){
            if(this.param.type == type) return
            this.param.type = type
            this.userList = this.users = [];
            this.loading = true;
            this.$refs.jsonPageNav.load(1, '', true)
        },
        handleInput() {
            if(this.searchText.trim()) {
                this.userList = [];
                this.loading = true;
            }
            // 如果不是中文输入状态，则调用getSuggestions方法
            if (!this.composing) {
                clearTimeout(this.typingTimer);
                this.typingTimer = setTimeout(() => {
                    this.circleSearchUsers();
                }, 500);
            }
        },
        circleSearchUsers() {
            if(!this.searchText.trim()) return;
            this.loading = true;
            let param = {
                key:this.searchText.trim(),
                circle_id:this.circle_id,
            }
            
            this.$https.post(qk_rest_url+'circleSearchUsers',param).then(res=>{
                this.userList = res.data
                this.loading = false;
            })
        },
        inviteUserJoinCircle(user_id,index){
            if(!confirm('确定要邀请这个用户入圈吗？')) return
            
            if(this.locked == true) return
            this.locked = true
            
            let param = {
                user_id:user_id,
                circle_id:this.circle_id,
            }
            
            this.$https.post(qk_rest_url+'inviteUserJoinCircle',param).then(res=>{
                this.userList[index].in_circle = true;
                this.users[index].in_circle = true;
                this.$message({ message: res.data.msg, type: 'success' });
                this.locked = false
            }).catch(err=>{
                this.locked = false
                this.$message({ message: err.response.data.message, type: 'error' });
            })
        },
        setUserCircleStaff(user_id,index){
            if(!confirm('确定要设置这个用户为版主吗？')) return
            
            if(this.locked == true) return;
            this.locked = true;
            
            let param = {
                user_id:user_id,
                circle_id:this.circle_id,
            }
            
            this.$https.post(qk_rest_url+'setUserCircleStaff',param).then(res=>{
                this.userList[index].role = 'staff';
                this.users[index].role = 'staff';
                
                this.$message({ message: res.data.msg, type: 'success' });
                this.locked = false
            }).catch(err=>{
                this.locked = false
                this.$message({ message: err.response.data.message, type: 'error' });
            })
        },
        removeCircleUser(user_id,index){
            
            if(!confirm('确定要移除这个用户吗？')) return
            
            if(this.locked == true) return;
            this.locked = true;
            
            let param = {
                user_id:user_id,
                circle_id:this.circle_id,
            }
            
            this.$https.post(qk_rest_url+'removeCircleUser',param).then(res=>{
                this.userList.splice(index, 1);
                this.users.splice(index, 1);
                
                this.$message({ message: res.data.msg, type: 'success' });
                
                this.locked = false
            }).catch(err=>{
                this.locked = false
                this.$message({ message: err.response.data.message, type: 'error' });
            })
        }
    },
    watch: {
        searchText() {
            this.searchText = this.searchText.trim()
            if(!this.searchText) {
                this.userList = this.users
            }
        }
        
    }
})

//文章管理组件
Vue.component('qk-circle-post-manage',{
    props: ['currentUser'],
    template: `
    <div class="post-manage form-container">
        <form>
            <label class="form-item">
                <input class="input" v-model="searchText" type="text" autocomplete="off" maxlength="100" placeholder="搜索圈子内帖子" @input="handleInput" @compositionstart="composing = true" @compositionend="composing = false">
                <span class="icon"><i class="ri-search-2-line"></i></span>
            </label>
        </form>
        <div class="circle-tabs-nav" v-if="!searchText">
            <div class="circle-tabs-nav-inner">
                <div class="tabs">
                    <ul class="tabs-nav">
                        <li :class="[{active:tabType == index}]" v-for="(item,index) in tabs" v-text="item" :key="index" @click="changeTab(index)"></li>
                    </ul>
                </div>
                <div class="orderby-wrap">
                    <div class="orderby">
                        <span>默认排序</span>
                        <i class="ri-arrow-down-s-line"></i>
                    </div>
                    <ul class="orderby-list box">
                        <li class="orderby-item" :class="[{active:orderby == index}]" v-for="(item,index) in orderbyList" v-text="item" :key="index" @click="changeOrderby(index)"></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="post-list post-2">
            <ul class="qk-grid scroll" v-if="postList.length">
                <li class="post-list-item" v-for="(item,index) in postList">
                    <div class="item-in">
                        <div class="post-module-thumb" v-if="item.attachment.image[0]">
                            <div class="qk-radius post-thumbnail" style="padding-top: 65%;">
                                <div class="thumb-link">
                                    <img class="post-thumb w-h qk-radius" :src="item.attachment.image[0].thumb">
                                </div>
                            </div>
                        </div>
                        <div class="post-info">
                            <h2 class="text-ellipsis" >
                                <span class="moment-best" v-if="item.best"></span>
                                <span class="moment-sticky" v-if="item.sticky"></span>
                                <span class="post-status" :class="item.status" v-text="item.status_name" v-if="item.status == 'pending'"></span>
                                <span v-html="item.title ? item.title : item.content"></span>
                            </h2>
                            <div class="post-info-buttom">
                                <div class="buttom-left">
                                    <span class="post-date" v-html="item.date"></span>
                                    <span class="post-views">
                                        阅读 {{item.meta.views}}
                                    </span>
                                    <span class="comment">
                                        评论 {{item.meta.comment}}
                                    </span>
                                    <span class="like">
                                        喜欢 {{item.meta.like.count}}
                                    </span>
                                    <span class="collect">
                                        收藏 {{item.meta.collect.count}}
                                    </span>
                                </div>
                                <div class="more-menu-box">
                                    <div class="more-menu-icon">
                                        <i class="ri-more-2-line"></i>
                                    </div>
                                    <ul class="more-menu-list box">
                                        <li v-if="item.status == 'pending'" @click="changeMomentStatus(item)">通过审核</li>
                                        <li @click="setMomentBest(item)">加精</li>
                                        <li @click="setMomentSticky(item)">置顶</li>
                                        <li @click="deleteMoment(item,index)">删除</li>
                                        <li @click="">编辑</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
            <div class="loading empty" v-else-if="loading && !postList.length" v-cloak></div>
            <template v-else-if="isDataEmpty">
                <div class="empty">
                    <img src="/wp-content/themes/qkua/Assets/fontend/images/empty.svg" class="empty-img">
                    <p class="empty-text">暂无数据</p>
                </div>
            </template>
        </div>
        <div class="qk-pagenav json-nav">
            <page-nav ref="jsonPageNav" paged="1" pages="1" navtype="json" type="page" :api="'getManageMomentList'" :param="param" @change="changePosts" v-once></page-nav>
        </div>
    </div>
    `,
    data() {
        return {
            param: {
                size: 10,
                circle_id:0,
                post_status:''
            },
            circle_id:0,
            posts:[],
            postList:[],
            searchText:'',
            composing:false,
            typingTimer: null,
            loading:false,
            isDataEmpty:false,
            orderby:'',
            orderbyList:{
                '':'默认排序',
                'date':'发布时间',
                'comment_date':'回复时间',
                'modified':'修改时间',
                'random':'随机排序',
                'comments':'评论数最多',
                'views':'浏览数最多',
                'like':'点赞数最多',
            },
            tabType:'all',
            tabs:{
                all:'全部',
                pending:'待审核',
            },
            locked:false
        }
    },
    mounted() {
        if(typeof qk_circle !== 'undefined') {
            this.circle_id = this.param.circle_id = qk_circle.id;
            this.param.circle_id
        }
        
        !this.postList.length && this.$refs.jsonPageNav.load(1, '', true);
    },
    methods: {
        changePosts(data){
            this.postList = this.posts = [];
            this.posts = data.data;
            this.postList = this.posts;
            this.loading = false;
            this.isDataEmpty = !this.postList.length
        },
        changeTab(type,orderby = false) {
            
            if(type == this.tabType && !orderby) return;
            this.tabType = type;
            this.param.post_status = ''
            if(this.tabType == 'pending'){
                this.param.post_status = this.tabType
            }
            
            this.$refs.jsonPageNav.pageCount = 1;
            this.loading = true;
            this.isDataEmpty = false;
            
            this.$nextTick(() => {
                this.$refs.jsonPageNav.load(1, '', true);
            });
        },
        changeOrderby(type) {
            this.param.orderby = this.orderby = type
            this.changeTab(this.tabType,true)
        },
        handleInput() {
            if(this.searchText.trim()) {
                this.postList = [];
                this.loading = true;
            }
            // 如果不是中文输入状态，则调用getSuggestions方法
            if (!this.composing) {
                clearTimeout(this.typingTimer);
                this.typingTimer = setTimeout(() => {
                    this.circleSearchPosts();
                }, 500);
            }
        },
        circleSearchPosts() {
            if(!this.searchText.trim()) return;
            this.loading = true;
            let param = {
                search:this.searchText.trim(),
                circle_id:this.circle_id,
            }

            this.$https.post(qk_rest_url+'getManageMomentList',param).then(res=>{
                this.postList = res.data.data
                
                this.isDataEmpty = !this.postList.length
                
                this.loading = false;
            }).catch(err=>{
                this.loading = false;
                this.$message({ message: err.response.data.message, type: 'error' });
            })
        },
        setMomentBest(item){
            if(this.locked == true) return
            this.locked = true
            
            this.$http.post(qk_rest_url + 'setMomentBest','post_id='+item.id).then(res=>{
                this.$message({ message: res.data.message , type: 'success' });
                item.best = !item.best
                this.locked = false
            }).catch(err => {
                this.locked = false
                this.$message({ message: err.response.data.message , type: 'error' });
            })
        },
        setMomentSticky(item){
            if(this.locked == true) return
            this.locked = true
            
            this.$http.post(qk_rest_url + 'setMomentSticky','post_id='+item.id).then(res=>{
                
                this.$message({ message: res.data.message , type: 'success' });
                item.sticky = !item.sticky
                this.locked = false
                
            }).catch(err => {
                this.locked = false
                this.$message({ message: err.response.data.message , type: 'error' });
            })
        },
        deleteMoment(item,index){
            if(!confirm('确定要删除这个帖子吗？')) return
            if(this.locked == true) return
            this.locked = true
            this.$http.post(qk_rest_url + 'deleteMoment','post_id='+item.id).then(res=>{
                this.$message({ message: res.data.message , type: 'success' });
                this.$nextTick(() => {
                    this.postList.splice(index, 1);
                    this.posts.splice(index, 1);
                });
                this.locked = false
            }).catch(err => {
                this.locked = false
                
                this.$message({ message: err.response.data.message , type: 'error' });
            })
        },
        changeMomentStatus(item){
            if(this.locked == true) return
            this.locked = true
            
            this.$http.post(qk_rest_url + 'changeMomentStatus','post_id='+item.id).then(res=>{
                this.$message({ message: '审核成功' , type: 'success' });
                item.status = 'publish';
                item.status_name = '已发布';
                this.locked = false
            }).catch(err => {
                this.locked = false
                this.$message({ message: err.response.data.message , type: 'error' });
            })
        },
    },
    watch: {
        searchText() {
            this.searchText = this.searchText.trim()
            if(!this.searchText) {
                //this.postList = this.posts
                this.$refs.jsonPageNav.load(1, '', true);
            }
        }
        
    }
})

Vue.component('createTopic',{
    props:['name'],
    template: `
    <div class="create-topic-container">
        <div class="colorful-header qk-flex">
            <div class="title">创建话题</div>
        </div>
        <div class="manage-content-wrap">
            <div class="form-container">
                <div class="base-manage">
                    <div class="circle-icon" @click="imgUploadBox">
                        <div class="icon-img" style=" display: contents; " v-colak>
                            <img :src="data.icon" class="w-h" v-if="data.icon">
                            <i class="ri-camera-fill" v-else></i>
                        </div>
                    </div>
                </div>
                <form @submit.stop.prevent="createTopic">
                    <label class="form-item">
                        <input type="text" autocomplete="off" v-model="data.name" maxlength="30" placeholder="话题名称" class="input">
                        <span class="icon">
                            <i class="ri-file-text-line"></i>
                        </span>
                    </label>
                    <label class="form-item">
                        <textarea placeholder="话题简介" v-model="data.desc"></textarea>
                        <span class="icon">
                            <i class="ri-file-text-line"></i>
                        </span>
                    </label>
                    <label class="form-item">
                        <input type="text" autocomplete="off" maxlength="10"  v-model="data.slug" placeholder="话题连接" class="input">
                        <span class="icon">
                            <i class="ri-link"></i>
                        </span>
                        <p class="desc">链接：{{window.location.origin}}/topic/{{data.slug}}</p>
                    </label>
                    <div class="form-button">
                        <button>确认</button>
                    </div>
                </form>
            </div>
        </div>
    </div>`,
    data() {
        return {
            data:{
                id:0,
                name:'',
                desc:'',
                icon:'',
                slug:'',
                original_icon: '',
            },
            locked:false
        };
    },
    mounted(){
        this.name && (this.data.name = this.name)
    },
    methods: {
        createTopic() {
            if(this.locked == true) return
            this.locked = true
            
            let params = {
                ...this.data,
            }

            params.icon = this.data.original_icon ? this.data.original_icon : params.icon;
            
            this.$http.post(qk_rest_url+'createTopic',params).then(res=>{
                this.locked = false
                this.$message({ message: '成功', type: 'success' });
                this.$emit('close-modal');
            }).catch(err=>{
                this.locked = false
                this.$message({ message: err.response.data.message, type: 'error' });
            })
        },
        imgUploadBox(imgtype) {
            //if(!this.currentUser.is_admin && !this.currentUser.is_circle_staff && !(this.currentUser.can_create_circle && this.isCreate)) return
            this.$createModal('imageUploadBox',{size:400,props:{
                data:{
                    showTabType:1,
                    maxPicked:1,
                    postType:'circle',
                    callback:(data,type) => {
                        this.data.icon = data[0]?.url
                        this.data.original_icon = ''
                    }
                }
                
            }})
        },
        destroy(){
            Object.assign(this.$data, this.$options.data.bind(this)());
        }
    },
})

/**
createModal('joinCircle',{
    loading:false,
})
**/
Vue.component('joinCircle',{
    props:['id'],
    template: `
    <div class="join-circle-container">
        <div class="circle-info">
            <div class="half-circle"></div>
            <div class="circle-image">
                <img :src="circle.icon" width="46" height="46" class="circle-image-face w-h">
                <div class="role-badge" v-text="circle_role.type_name"></div>
            </div>
            <div class="circle-name" v-text="circle.name"></div>
            <p>欢迎您即将成为第 <span v-text="parseInt(circle.user_count) + 1"></span> 位成员</p>
        </div>
        <div class="roles-box" v-if="circle_role.type == 'roles'">
            <div class="separator-text"">专属圈子，以下用户组可加入</div>
            <ul class="roles-list">
                <li class="role-item" v-for="(item,index) in circle_role.roles" v-text="item.name"></li>
            </ul>
        </div>
        <div class="pay-group-box" v-if="circle_role.type == 'credit' || circle_role.type == 'money'">
            <div class="separator-text">付费加入圈子</div>
            <ul class="pay-group-list">
                <li class="pay-item pay-info" v-for="(item,index) in circle_role.pay_group" :class="{'bg-text':payIndex == index}" @click="payIndex = index">
                    <div class="pay-info-title" v-text="item.name"></div>
                    <div class="pay-info-price">
                        <span class="left">服务时间：{{item.time == 0 ? '永久有效' : item.time + '天'}}</span>
                        <span class="rigth">
                            <div class="pay-price">
                                <span class="unit">{{circle_role.type == 'credit' ? '积分' : '￥'}}</span>
                                <span class="num">{{item.price}}.00</span>
                            </div>
                        </span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="confirm-button join-circle">
            <button @click="passwordJoin" v-if="circle_role.type == 'password'">输入密码加入</button>
            <button @click="joinCircle" v-else-if="circle_role.type == 'free'">免费加入</button>
            <button @click="joinCircle" v-else-if="circle_role.type == 'roles' && circle_role.allow">加入圈子</button>
            <button @click="payJoin" v-else-if="circle_role.type == 'credit' || circle_role.type == 'money'">支付入圈</button>
            <button disabled v-else>无权加入</button>
        </div>
    </div>`,
    data() {
        return {
            circle:'',
            locked:false,
            circle_role:'',
            password:'',
            payIndex:0,
        };
    },
    mounted(){
        if(!qktoken) return this.$createModal('login');
        
        if(typeof qk_circle != 'undefined'){
            this.circle = qk_circle
            this.getCircleRoleData()
        }
    },
    methods: {
        getCircleRoleData(){
            this.$http.post(qk_rest_url+'getCircleRoleData',{circle_id:this.circle.id}).then(res=>{
                this.circle_role = res.data
                this.$emit('loadinged')
            })
        },
        payJoin() {
            
            if(!this.circle_role.pay_group[this.payIndex]) return
            
            qkpay({
                order_price: this.circle_role.pay_group[this.payIndex].price,
                order_type: 'join_circle',
                post_id: this.circle.id,
                title: '加入圈子',
                type: this.circle_role.type,
                tag: this.circle_role.pay_group[this.payIndex].name,
                order_key: this.payIndex,
                order_value: this.circle_role.pay_group[this.payIndex].time
            })
        },
        passwordJoin() {
            let data = qk_global.password_verify;
            data.post_id = this.circle.id;
            data.type = 'circle';
            data.confirm = (params)=>{
                this.password = params.code
                this.joinCircle()
            }
            
           this.$createModal('passwordVerify',{
                size:312,
                loading:false,
                props:{
                    data
                },
            })
        },
        joinCircle(){
            if(this.locked == true) return;
            this.locked = true
            
            let params = {
                circle_id:this.circle.id,
                password:this.password,
                type:this.circle_role.type
            }
            
            this.$http.post(qk_rest_url+'joinCircle',params).then(res=>{
                this.locked = false
                this.$emit('close-drawer');
                this.$message({ message: '加入成功', type: 'success' });
                //刷新当前页面
                setTimeout(()=>{
                    qkCurrentPageReload()
                }, 2000)
            }).catch(err=>{
                this.locked = false
                this.$message({ message: err.response.data.message, type: 'error' });
            })
        }
    },
    watch: {

    },
})

var circleSwiperCarousel = new Vue({
    el:'.swiper-carousel',
    data: {
        currentIndex: 0,
        timer: null,
        debounceTimer: null,
        carouselLength: 0,
        autoPlayDuration: 4000  // Default autoplay duration
    },
    computed: {
        getCarouselItemClasses() {
            return (index) => {
                let prev1 = (this.currentIndex - 1 +  this.carouselLength) % this.carouselLength;
                let prev2 = (this.currentIndex - 2 +  this.carouselLength) % this.carouselLength;
                let next1 = (this.currentIndex + 1) % this.carouselLength;
                let next2 = (this.currentIndex + 2) % this.carouselLength;
                return {
                    'active': index === this.currentIndex,
                    'prev': index === prev1,
                    'prev-1': this.carouselLength >= 5 && index === prev2,
                    'next': index === next1,
                    'next-1': this.carouselLength >= 5 && index === next2
                };
            };
        }
    },
    mounted() {
        this.parseCarouselData();
        lazyLoadInstance.update()
        this.startAutoPlay();
        document.addEventListener('visibilitychange', this.handleVisibilityChange);
    },
    methods: {
        startAutoPlay() {
            this.timer = setInterval(() => {
                this.next();
            }, this.autoPlayDuration); // 每隔4秒自动切换
        },
        prev() {
            this.currentIndex = (this.currentIndex - 1 + this.carouselLength) % this.carouselLength;
        },
        next() {
            this.currentIndex = (this.currentIndex + 1) % this.carouselLength;
        },
        click(index) {
            this.currentIndex = index;
        },
        handleVisibilityChange() {
            if (document.hidden) {
                clearInterval(this.timer); // 在页面不可见时暂停自动播放
            } else {
                this.startAutoPlay(); // 在页面可见时恢复自动播放
            }
        },
        handleMouseEnter() {
            clearInterval(this.timer); // 当鼠标悬停在图片或点上时暂停自动播放
        },
        handleMouseLeave() {
            this.startAutoPlay(); // 使用防抖函数处理恢复自动播放
        },
        parseCarouselData() {
            const carouselContainer = this.$refs.carouselContainer;
            if (carouselContainer) {
                const carouselData = JSON.parse(carouselContainer.getAttribute('carousel-data'));
                this.carouselLength = carouselData.length;
                this.autoPlayDuration = carouselData.autoPlay || 4000; 
            }
        }
    },
    beforeDestroy() {
        clearInterval(this.timer); // 在组件销毁前清除定时器
        document.removeEventListener('visibilitychange', this.handleVisibilityChange);
    }
})

var circlewidget = new Vue({
    el:'.circle-channel-menu-widget',
    data: {
        index: 0,
        tabs: null
    },
    mounted() {
        if(!this.$refs.channelMenu)return
        this.index = this.$refs.channelMenu.getAttribute('data-index')
    },
    methods: {
        changeTab(index,type,orderby = false){
            if(this.index == index) return
            this.index = index
            qkMomentList.changeTab(index,type,orderby)
        },
        createCircle(){
            createDrawer({componentName:'circleManage',componentProps: {
                type: 'create' // 传递给组件的props
            }})
        },
    }
})

var circleplayerWrap = new Vue({
    el:'.single-circle .qk-player-wrap',
    data:{
        player:'',
        post_id:qk_global.post_id,
        videoList:[],
    },
    mounted(){
        if(!this.$refs.player) return
        this.$http.post(qk_rest_url+'getMomentVideoList','post_id='+this.post_id).then(res=>{
            this.videoList = this.convertDataToVideoList(res.data);
            if(this.videoList.length){
                this.player = new MoePlayer({
                    container: document.getElementById('moeplayer'),
                    autoplay:true,
                    autonext:false,
                    theme:"var(--theme-color)",
                    videoList:this.videoList,
                    videoIndex:0,
                });
            }
        })
    },
    methods:{
        convertDataToVideoList(data) {
            let videoList = [];
            
            // 检查数据是否存在以及是否为数组
            if (data && Array.isArray(data)) {
                data.forEach(video => {
                    videoList.push({
                        id: video.post_id,
                        url: video.url,
                        pic: video.poster,
                    });
                });
            }
            
            return videoList;
        }
    }
})