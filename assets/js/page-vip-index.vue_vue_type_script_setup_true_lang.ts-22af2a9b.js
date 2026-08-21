import{U as defineComponent,r as ref,$ as useRouter,Z as h}from"./common.modules-219f1756.js";
import{A as request,cr as getVipDetail,ct as getVipRewards}from"./page-activity-ActivityDetail-346b11b6.js";

const apiFallback="/assets/png/jalwa-avatar-01.png";
const rewardDefaults=[
  {type:1,title:"Level up rewards",description:"Each account can only receive 1 time",value:"60",kind:"gift"},
  {type:2,title:"Monthly reward",description:"Each account can only receive 1 time per month",value:"3",kind:"coin"},
  {type:3,title:"Rebate rate",description:"Increase income of rebate",value:"0.04%",kind:"rebate"}
];
function readUser(){try{return JSON.parse(localStorage.getItem("userInfo")||"{}")}catch(e){return{}}}
function number(value){const n=Number(value);return Number.isFinite(n)?n:0}
function pct(value,total){return total>0?Math.max(0,Math.min(100,Math.round(value/total*10000)/100)):0}
function icon(kind){const styles={gift:"background:#51455a;color:#ffd65d",coin:"background:#51455a;color:#ffcc45",rebate:"background:#51455a;color:#22e1c7"};return h("div",{style:`width:42px;height:42px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:900;${styles[kind]||styles.gift}`},kind==="gift"?"▣":kind==="coin"?"◉":"≋")}
function rewardCards(items){
  const list=items&&items.length?items:rewardDefaults;
  return list.slice(0,3).map((item,index)=>{
    const base=rewardDefaults[index]||rewardDefaults[2];
    const value=item.rewardType===1?String(number(item.balance)||base.value):item.rewardType===2?String(number(item.balance)||base.value):base.value;
    return h("div",{key:String(item.rewardType||index),style:"display:flex;align-items:center;gap:14px;margin:20px 0"},[
      icon(base.kind),
      h("div",{style:"flex:1;min-width:0"},[
        h("div",{style:"font-size:18px;color:#f4f7ff;font-weight:600"},base.title),
        h("div",{style:"font-size:14px;color:#a9bbdf;line-height:1.35;margin-top:5px"},base.description)
      ]),
      h("div",{style:"min-width:92px;text-align:center;color:#ffd35c;border:1px solid #d7a94d;border-radius:8px;padding:7px 6px;font-size:17px;font-weight:700"},value)
    ])
  })
}
const VipPage=defineComponent({
  name:"JalwaVipPage",
  setup(){
    const router=useRouter();
    const user=ref(readUser());
    const levels=ref([]);
    const rewards=ref([]);
    const currentExp=ref(0);
    const currentLevel=ref(number(user.value.vipLevel));
    const activeTab=ref("history");
    const loading=ref(true);
    const load=async()=>{
      user.value=readUser();
      currentLevel.value=number(user.value.vipLevel);
      try{
        const detail=await request(getVipDetail());
        if(detail&&detail.code===0&&Array.isArray(detail.data)){
          levels.value=detail.data;
          const selected=detail.data.find(x=>number(x.id)===currentLevel.value)||detail.data[0];
          if(selected)currentExp.value=number(selected.currentExp);
        }
        const target=currentLevel.value>0?currentLevel.value:1;
        const reward=await request(getVipRewards({vipLevel:target}));
        if(reward&&reward.code===0&&Array.isArray(reward.data))rewards.value=reward.data;
      }catch(e){}
      loading.value=false;
    };
    load();
    return()=>{
      const nextId=currentLevel.value+1>0?currentLevel.value+1:1;
      const card=levels.value.find(x=>number(x.id)===nextId)||levels.value[0]||{id:1,vipName:"VIP1",upgrade:3000,currentExp:currentExp.value};
      const exp=number(card.currentExp||currentExp.value);
      const upgrade=number(card.upgrade)||3000;
      const progress=pct(exp,upgrade);
      const avatar=user.value.userPhoto||apiFallback;
      const nickname=String(user.value.nickName||"Member").toUpperCase();
      return h("div",{style:"min-height:100vh;background:#08052d;color:#f3f6ff;font-family:Arial,sans-serif;padding-bottom:28px"},[
        h("div",{style:"height:78px;display:flex;align-items:center;justify-content:center;position:relative;font-size:25px;font-weight:500"},[
          h("button",{onClick:()=>router.go(-1),style:"position:absolute;left:18px;top:18px;border:0;background:transparent;color:#fff;font-size:38px;line-height:38px"},"‹"),
          h("span",null,"VIP")
        ]),
        h("div",{style:"background:#061b55;padding:5px 28px 28px;min-height:160px"},[
          h("div",{style:"display:flex;align-items:center;gap:16px;margin-top:0"},[
            h("img",{src:avatar,onError:e=>{e.currentTarget.src=apiFallback},style:"width:116px;height:116px;border-radius:50%;object-fit:cover;border:2px solid #d6e4fb"}),
            h("div",{style:"flex:1"},[
              h("div",{style:"display:flex;align-items:center;gap:10px;margin-bottom:10px"},[
                h("div",{style:"width:84px;height:29px;border-radius:6px;background:linear-gradient(135deg,#eef4fa,#9daec0);color:#657488;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:800;box-shadow:0 1px 3px #0006"},"VIP"+String(currentLevel.value)),
                h("span",{style:"font-size:20px;color:#f6f8ff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"},nickname)
              ]),
              h("div",{style:"color:#dce7f6;font-size:16px"},currentLevel.value+" VIP level")
            ])
          ]),
          h("div",{style:"display:grid;grid-template-columns:1fr 1fr;gap:36px;margin-top:22px"},[
            h("div",{style:"background:#062563;border-radius:8px;text-align:center;padding:17px 8px"},[
              h("div",{style:"color:#20e6d2;font-size:27px;font-weight:800"},`${exp} EXP`),
              h("div",{style:"color:#b6c9ea;font-size:16px;margin-top:9px"},"My experience")
            ]),
            h("div",{style:"background:#062563;border-radius:8px;text-align:center;padding:17px 8px"},[
              h("div",{style:"color:#f3f6ff;font-size:27px;font-weight:800"},["11 ",h("span",{style:"font-size:17px;font-weight:400;color:#a8bce2"},"Days")]),
              h("div",{style:"color:#b6c9ea;font-size:16px;margin-top:9px"},"Payout time")
            ])
          ])
        ]),
        h("div",{style:"margin:0 28px;padding:18px 10px;border:1px solid #162b62;border-radius:8px;color:#b8c9e8;font-size:15px;text-align:center"},"VIP level rewards are settled at 2:00 am on the 1st of every month"),
        h("div",{style:"margin:16px 28px 0;background:linear-gradient(135deg,#b7c8db,#a7bad1);border-radius:9px;padding:20px;color:#fff;box-shadow:0 8px 20px #0003"},[
          h("div",{style:"display:flex;align-items:center;justify-content:space-between"},[
            h("div",null,[h("div",{style:"font-size:29px;font-weight:800"},"✓ "+(card.vipName||"VIP1")),h("div",{style:"font-size:17px;margin-top:8px"},`Upgrading ${card.vipName||"VIP1"} requires ${upgrade}EXP`),h("div",{style:"display:inline-block;margin-top:16px;padding:5px 8px;border:1px solid #d8e5f3;border-radius:5px;font-size:14px"},"Bet ₹1=1EXP")]),
            h("div",{style:"width:88px;height:88px;border-radius:50%;border:7px solid #eef5ff;display:flex;align-items:center;justify-content:center;color:#637489;font-size:20px;font-weight:800;background:#c3d0df"},"VIP")
          ]),
          h("div",{style:"height:12px;background:#8398ae;border-radius:10px;margin-top:20px;overflow:hidden"},h("div",{style:`width:${progress}%;height:100%;background:#f4f7ff;border-radius:10px`})),
          h("div",{style:"display:flex;justify-content:space-between;font-size:15px;margin-top:6px"},[h("span",null,`${exp}/${upgrade}`),h("span",null,`${upgrade} EXP can be leveled up`)])
        ]),
        h("div",{style:"margin:22px 28px 0;background:#061b55;border-radius:9px;padding:22px 18px"},[
          h("div",{style:"display:flex;align-items:center;gap:10px;color:#eef5ff;font-size:25px;font-weight:700"},[h("span",{style:"color:#22e1c7;font-size:28px"},"◇"),`${card.vipName||"VIP1"} Benefits level`]),
          loading.value?h("div",{style:"color:#a9bbdf;padding:26px 0"},"Loading benefits..."):rewardCards(rewards.value)
        ]),
        h("div",{style:"display:grid;grid-template-columns:1fr 1fr;margin:20px 28px 0;background:#061b55;border-radius:8px 8px 0 0;overflow:hidden"},[
          h("button",{onClick:()=>activeTab.value="history",style:`border:0;border-bottom:3px solid ${activeTab.value==="history"?"#20e6d2":"transparent"};background:transparent;color:${activeTab.value==="history"?"#20e6d2":"#a8bce2"};font-size:20px;padding:16px`},"History"),
          h("button",{onClick:()=>activeTab.value="rules",style:`border:0;border-bottom:3px solid ${activeTab.value==="rules"?"#20e6d2":"transparent"};background:transparent;color:${activeTab.value==="rules"?"#20e6d2":"#a8bce2"};font-size:20px;padding:16px`},"Rules")
        ]),
        h("div",{style:"margin:0 28px;background:#061b55;padding:18px;color:#a8bce2;border-radius:0 0 8px 8px;text-align:center;font-size:14px"},activeTab.value==="history"?"No VIP reward history yet":"VIP rewards are settled monthly; upgrade EXP is calculated from qualifying bets.")
      ]);
    };
  }
});
export{VipPage as _};
