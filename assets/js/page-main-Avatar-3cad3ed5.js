import{U as defineComponent,r as ref,$ as useRouter,Z as h}from"./common.modules-219f1756.js";

const defaultAvatar="/assets/png/avatar1-2f23f3bd.png";
const apiBase="/deepanshu/api/webapi/";

function token(){return localStorage.getItem("token")||""}
function auth(){const t=token();return t?{"Authorization":"Bearer "+t,"Content-Type":"application/json"}:{"Content-Type":"application/json"}}
async function savePhoto(value){
  const response=await fetch(apiBase+"EditUserPhoto.php",{method:"POST",headers:auth(),body:JSON.stringify({userPhoto:value})});
  return response.json();
}

const Avatar=defineComponent({
  name:"Avatar",
  setup(){
    const router=useRouter();
    const current=ref(defaultAvatar);
    const busy=ref(false);
    const message=ref("");
    try{const info=JSON.parse(localStorage.getItem("userInfo")||"{}");if(info.userPhoto)current.value=info.userPhoto}catch(e){}
    const close=()=>router.go(-1);
    const choose=async value=>{busy.value=true;message.value="";try{const result=await savePhoto(value);if(result&&result.code===0){message.value="Profile picture updated";try{const info=JSON.parse(localStorage.getItem("userInfo")||"{}");info.userPhoto=value;localStorage.setItem("userInfo",JSON.stringify(info))}catch(e){};setTimeout(close,350)}else message.value=result?.msg||"Unable to update profile picture"}catch(e){message.value="Unable to update profile picture"}finally{busy.value=false}};
    const upload=event=>{const file=event.target.files?.[0];if(!file)return;if(!/^image\/(png|jpeg|webp)$/.test(file.type)||file.size>1500000){message.value="Choose a PNG, JPG, or WebP image under 1.5 MB";return}const reader=new FileReader();reader.onload=()=>choose(reader.result);reader.readAsDataURL(file)};
    return()=>h("div",{class:"avatar-page",style:"min-height:100vh;background:#08052d;color:#fff;padding:24px 18px;font-family:Arial,sans-serif"},[
      h("div",{style:"display:flex;align-items:center;gap:12px;margin-bottom:24px"},[h("button",{onClick:close,style:"border:0;background:transparent;color:#fff;font-size:28px"},"‹"),h("h2",{style:"margin:0"},"Change avatar")]),
      h("div",{style:"text-align:center;background:#071b55;border-radius:18px;padding:24px"},[
        h("img",{src:current.value,style:"width:116px;height:116px;border-radius:50%;object-fit:cover;border:4px solid #2ee6d1"}),
        h("p",{style:"opacity:.8"},"Choose an avatar or upload your own picture"),
        h("div",{style:"display:flex;justify-content:center;gap:14px;flex-wrap:wrap;margin:18px 0"},[
          h("img",{src:"/assets/png/avatar1-2f23f3bd.png",onClick:()=>choose("1"),style:"width:64px;height:64px;border-radius:50%;object-fit:cover"}),
          h("img",{src:"/assets/png/avatar-5a79e664.png",onClick:()=>choose("2"),style:"width:64px;height:64px;border-radius:50%;object-fit:cover"}),
          h("img",{src:"/assets/png/avatar-ea3b8ee9.png",onClick:()=>choose("3"),style:"width:64px;height:64px;border-radius:50%;object-fit:cover"})
        ]),
        h("label",{style:"display:inline-block;background:#21dcca;color:#06133e;padding:13px 22px;border-radius:24px;font-weight:700"},["Upload picture",h("input",{type:"file",accept:"image/png,image/jpeg,image/webp",onChange:upload,style:"display:none"})]),
        busy.value?h("p",null,"Saving..."):null,
        message.value?h("p",{style:"color:#ffd166"},message.value):null
      ])
    ]);
  }
});
export default Avatar;
