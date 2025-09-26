var qkVerifyPage = new Vue({
    el:'.verify-body',
    data:{
        data:[],
        step:0,
        currentVerifyData:{},
        // currentIndex:0,
        status:0,
        opinion:'',
        locked:false,
        protocol:false, //协议
        formData:{
            index:0,
            type:'', //认证类型
            title:'', //认证信息
            company:'', //公司名称
            credit_code:'', //信用代码
            business_license:'', //营业执照
            business_auth:'', //认证申请公函
            official_site:'', //官方网站
            supplement:'', //补充资料
            // name:'',
            operator:'',//运营者
            email:'',
            telephone:'', //运营者手机号
            id_card:'', //身份正在号
            idcard_hand:'',//手持身份证
            idcard_front:'',//身份证正面
            idcard_verso:'', //身份证背面
            
        }
    },
    mounted() {
        // if(this.$refs.verifyPage){
            
            // this.getVerifyInfo()
            
            // if(qktoken) {
                this.getUserVerifyInfo()
            // };
        // }
    },
    computed: {
        //不满足的条件计数
        unsatisfiedCount() {
            const countByType = {};
            this.data.forEach(item => {
                const unsatisfiedCount = item.conditions.filter(condition => !condition.allow).length;
                if (!countByType[item.type]) {
                    countByType[item.type] = unsatisfiedCount;
                } else {
                    countByType[item.type] += unsatisfiedCount;
                }
            });
            return countByType;
        }
    },
    methods:{
        getVerifyInfo(){
            this.$http.post(qk_rest_url+'getVerifyInfo').then(res=>{
                this.data = res.data
                // 对conditions按照allow属性进行排序
                this.data.forEach(item => {
                    item.conditions.sort((a, b) => {
                        return a.allow === b.allow ? 0 : a.allow ? -1 : 1;
                    });
                });
                
                // 查找符合条件的元素的索引
                this.formData.index = this.data.findIndex(item => item.type === this.formData.type);
                
                this.$nextTick(()=>{
                    this.currentVerifyData = this.data[this.formData.index]
                })
            })
        },
        getUserVerifyInfo() {
            this.$http.post(qk_rest_url+'getUserVerifyInfo').then(res=>{
                
                const data = res.data
                this.step = data.step
                this.status = data.status
                this.opinion = data.opinion
                this.formData = {...this.formData,...data.data}
                this.formData.type = data.type
                this.formData.title = data.title
                
                this.getVerifyInfo()
            })
        },
        onNext(val,index) {
            this.currentVerifyData = val
            this.step = 2
            this.formData.type = val.type
            this.formData.index = index
        },
        onPrev() {
            this.step -= this.step == 4 ? 2 : 1
            this.status = 0
        },
        pay(data,index) {
            if(!qktoken) return this.$createModal('login');
            qkpay({
                'title': this.data[index].name,
                'order_price': data.value,
                'order_type':'verify',
                'order_key':index,
                'order_value' : this.data[index].type,
                'type':data.key,
                'tag':'认证付费'
            },data.key)
        },
        handleUpload(event,type) {
            // if (this.$refs.uploadInput.files.length <= 0) return;
            if (this.locked) return;
            this.locked = true;
            
            let file = event.target.files[0];
            this.formData[type] = URL.createObjectURL(file);
            let formData = new FormData()

            formData.append('file',file,file.name)
            formData.append("post_id", 1)
            formData.append("type", 'verify')
            
            this.$http.post(qk_rest_url + 'fileUpload',formData).then(res=>{
                this.locked = false;
                this.formData[type] = res.data.url;
            }).catch(err=>{
                this.formData[type] = ''
                
                let msg = err.response.data.message
                msg = msg ? msg : '上传失败，请重新上传';
                this.$message({ message: msg, type: 'warning' });
                this.locked = false;
            })
            
            this.$refs.uploadInput.value = null;
        },
        submitVerify(){
            if(this.locked == true) return
            this.locked = true
            this.$http.post(qk_rest_url + 'submitVerify',this.formData).then(res=>{
                this.step = 3
                this.$message({ message: '申请已提交，等待认证审核', type: 'success' });
                this.locked = false
            }).catch(err=>{
                this.locked = false
                this.$message({ message: err.response.data.message, type: 'error' });
            })
        }
    }
    
})