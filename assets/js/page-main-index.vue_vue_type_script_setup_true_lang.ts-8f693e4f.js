import{U as defineComponent,r as ref,$ as useRouter,Z as h}from"./common.modules-219f1756.js";

const fallbackLogo="/assets/png/logo-8e1d7dae.png";
const categories=[
  {label:"Lottery",asset:"/assets/png/home-category-lottery.png",route:"Lottery"},
  {label:"Mini games",asset:"/assets/png/home-category-mini-games.png",route:"AllGames"},
  {label:"Hot Slots",asset:"/assets/png/home-category-hot-slots.png",route:"Slots"},
  {label:"Slots",asset:"/assets/png/home-category-slots.png",route:"Slots"},
  {label:"Fishing",asset:"/assets/png/home-category-fishing.png",route:"Fishing"},
  {label:"PVC",asset:"/assets/png/home-category-pvc.png",route:"PVC"},
  {label:"Casino",asset:"/assets/png/home-category-casino.png",route:"Casino"},
  {label:"Sports",asset:"/assets/png/home-category-sports.png",route:"eSports"}
];
function readUser(){try{return JSON.parse(localStorage.getItem("userInfo")||"{}")}catch(e){return{}}}
function balance(user){const value=Number(user.amount??user.walletAmount??user.balance??0);return Number.isFinite(value)?value.toFixed(2):"0.00"}
function go(router,name){try{const result=router.push({name});if(result&&result.catch)result.catch(()=>{})}catch(e){}}
function cardIcon(symbol,color){return h("div",{style:`width:48px;height:48px;border-radius:15px;background:${color};display:flex;align-items:center;justify-content:center;color:#fff;font-size:25px;font-weight:900;box-shadow:0 4px 9px #0005`},symbol)}
const Home=defineComponent({name:"JalwaHome",setup(){
  const router=useRouter();
  const user=ref(readUser());
  const refresh=()=>{user.value=readUser()};
  if(typeof window!=="undefined")window.addEventListener("jalwa-avatar-updated",refresh);
  return()=>{
    const info=user.value||{};
    return h("div",{style:"min-height:100vh;background:#08052d;color:#f2f6ff;font-family:Arial,sans-serif;padding-bottom:92px;overflow-x:hidden"},[
      h("header",{style:"height:74px;padding:10px 22px;display:flex;align-items:center;justify-content:space-between;background:#08052d"},[
        h("img",{src:fallbackLogo,onError:e=>{e.currentTarget.src="/assets/png/logo1.png"},style:"width:180px;height:48px;object-fit:contain;object-position:left center"}),
        h("div",{style:"display:flex;align-items:center;gap:14px"},[
          h("span",{style:"font-size:30px;color:#20e6d2"},"⌄"),
          h("img",{src:"/assets/png/en-4c6eba8e.png",style:"width:34px;height:34px;border-radius:50%"}),
          h("span",{style:"font-size:18px;color:#20e6d2"},"EN")
        ])
      ]),
      h("main",{style:"padding:0 22px"},[
        h("div",{style:"display:grid;grid-template-columns:1fr 1fr;gap:14px;margin:8px 0 28px"},[
          h("button",{onClick:()=>go(router,"Turntable"),style:"border:0;border-radius:16px;background:linear-gradient(120deg,#20cce2,#26d76e);height:86px;color:#fff;text-align:left;padding:0 20px;font-size:23px;font-weight:800;overflow:hidden"},[h("div",null,"Wheel"),h("div",null,"of fortune")]),
          h("button",{onClick:()=>go(router,"vip"),style:"border:0;border-radius:16px;background:linear-gradient(120deg,#298de7,#18d9e0);height:86px;color:#fff;text-align:left;padding:0 20px;font-size:23px;font-weight:800"},[h("div",null,"VIP"),h("div",null,"privileges")])
        ]),
        h("div",{style:"border-radius:18px;overflow:hidden;box-shadow:0 8px 20px #0006"},[
          h("img",{src:"/assets/png/home-supplied-hero.jpg",style:"width:100%;display:block;aspect-ratio:2.125;object-fit:cover"})
        ]),
        h("div",{style:"display:flex;justify-content:center;gap:14px;margin:16px 0 22px"},Array.from({length:12},(_,i)=>h("span",{key:i,style:`width:${i===1?30:9}px;height:9px;border-radius:8px;background:${i===1?"#20e6d2":"#7b7e91"}`}))),
        h("div",{style:"border:1px solid #1d5b96;border-radius:18px;background:#071d56;min-height:60px;padding:9px 12px;display:flex;align-items:center;gap:12px;margin-bottom:24px"},[
          h("span",{style:"font-size:24px;color:#20e6d2"},"◖"),
          h("div",{style:"flex:1;color:#e8edf8;font-size:14px;line-height:1.25;max-height:38px;overflow:hidden"},"हमारी कस्टमर सर्विस कभी भी सदस्यों को कोई लिंक नहीं भेजेगी — यदि आपको कोई लिंक मिले तो उसे न खोलें।"),
          h("button",{onClick:()=>go(router,"CustomerService"),style:"border:0;border-radius:22px;background:linear-gradient(90deg,#21d6cf,#69eead);color:#07335c;font-size:16px;padding:12px 20px;white-space:nowrap"},"Detail")
        ]),
        h("section",{style:"display:flex;align-items:center;justify-content:space-between;margin:0 0 22px"},[
          h("div",null,[h("div",{style:"font-size:15px;color:#aac0df"},[h("span",{style:"color:#ffd85e;font-size:20px"},"● "),"Wallet balance"]),h("div",{style:"font-size:28px;font-weight:800;margin-top:6px"},["₹"+balance(info)+" ",h("span",{style:"font-size:22px;color:#92a8cc"},"⟳")])]),
          h("div",{style:"display:flex;gap:12px"},[
            h("button",{onClick:()=>go(router,"Withdraw"),style:"border:0;border-radius:13px;background:linear-gradient(135deg,#ffb650,#f37d50);color:#fff;font-size:17px;padding:13px 17px"},[h("div",{style:"font-size:25px;font-weight:800"},"↑"),"Withdraw"]),
            h("button",{onClick:()=>go(router,"Recharge"),style:"border:0;border-radius:13px;background:linear-gradient(135deg,#ff716b,#f1494d);color:#fff;font-size:17px;padding:13px 20px"},[h("div",{style:"font-size:25px;font-weight:800"},"＋"),"Deposit"])
          ])
        ]),
        h("section",{style:"display:grid;grid-template-columns:repeat(4,1fr);gap:18px 14px"},categories.map(category=>h("button",{key:category.label,onClick:()=>go(router,category.route),style:"border:0;background:transparent;color:#aab8d2;font-size:15px;padding:0;min-width:0"},[
          h("div",{style:"height:82px;border-radius:18px;background:linear-gradient(150deg,#112a6b,#06194c);display:flex;align-items:center;justify-content:center;box-shadow:inset 0 0 0 1px #1b5487,0 5px 10px #0005"},h("img",{src:category.asset,style:"width:100%;height:100%;object-fit:contain"})),
          h("div",{style:"margin-top:7px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"},category.label)
        ]))),
        h("h2",{style:"font-size:22px;margin:28px 0 10px"},[h("span",{style:"color:#e6f0ff"},"◉ "),"Lottery"])
      ]),
      h("button",{onClick:()=>go(router,"CustomerService"),style:"position:fixed;right:20px;bottom:115px;width:62px;height:62px;border:0;border-radius:50%;background:#21d6cf;color:#fff;font-size:30px;box-shadow:0 3px 10px #0007;z-index:4"},"◔"),
      h("nav",{style:"position:fixed;left:0;right:0;bottom:0;height:82px;background:#08052d;border-top:1px solid #163273;display:grid;grid-template-columns:repeat(5,1fr);z-index:3"},[
        h("button",{onClick:()=>go(router,"Promotion"),style:"border:0;background:transparent;color:#8693b5;font-size:14px"},[cardIcon("♥","#26305c"),"Promotion"]),
        h("button",{onClick:()=>go(router,"Activity"),style:"border:0;background:transparent;color:#8693b5;font-size:14px"},[cardIcon("▣","#26305c"),"Activity"]),
        h("button",{style:"border:0;background:#07536b;color:#20e6d2;font-size:14px;border-radius:30px 30px 0 0"},[cardIcon("⌁","#0c6f7b"),"Home"]),
        h("button",{onClick:()=>go(router,"Wallet"),style:"border:0;background:transparent;color:#8693b5;font-size:14px"},[cardIcon("▰","#26305c"),"Wallet"]),
        h("button",{onClick:()=>go(router,"SettingCenter"),style:"border:0;background:transparent;color:#20e6d2;font-size:14px"},[cardIcon("●","#0b8e86"),"Account"])
      ])
    ]);
  };
}});
export{Home as _};
