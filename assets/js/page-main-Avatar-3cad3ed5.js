import{U as defineComponent,r as ref,$ as useRouter,Z as h}from"./common.modules-219f1756.js";
const avatarChoices=[
  "/assets/png/jalwa-avatar-01.png",
  "/assets/png/jalwa-avatar-02.png",
  "/assets/png/jalwa-avatar-03.png",
  "/assets/png/jalwa-avatar-04.png",
  "/assets/png/jalwa-avatar-05.png",
  "/assets/png/jalwa-avatar-06.png",
  "/assets/png/jalwa-avatar-07.png",
  "/assets/png/jalwa-avatar-08.png",
  "/assets/png/jalwa-avatar-09.png",
  "/assets/png/jalwa-avatar-10.png",
  "/assets/png/jalwa-avatar-11.png",
  "/assets/png/jalwa-avatar-12.png",
  "/assets/png/jalwa-avatar-13.png",
  "/assets/png/jalwa-avatar-14.png",
  "/assets/png/jalwa-avatar-15.png",
  "/assets/png/jalwa-avatar-16.png",
  "/assets/png/jalwa-avatar-17.png"
];
const defaultAvatar=avatarChoices[0];
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
    try{
      const info=JSON.parse(localStorage.getItem("userInfo")||"{}");
      const legacyAvatars=["/assets/png/avatar1-2f23f3bd.png","/assets/png/avatar-5a79e664.png","/assets/png/avatar-ea3b8ee9.png"];
      if(info.userPhoto&&!legacyAvatars.includes(info.userPhoto))current.value=info.userPhoto;
    }catch(e){}
    const close=()=>router.go(-1);
    const choose=async choice=>{
      if(busy.value)return;
      busy.value=true;
      message.value="";
      const previous=current.value;
      current.value=choice.path;
      try{
        const result=await savePhoto(choice.id);
        if(result&&result.code===0){
          message.value="Profile picture updated";
          try{
            const info=JSON.parse(localStorage.getItem("userInfo")||"{}");
            info.userPhoto=choice.path;
            localStorage.setItem("userInfo",JSON.stringify(info));
            window.dispatchEvent(new CustomEvent("jalwa-avatar-updated",{detail:{userPhoto:choice.path}}));
          }catch(e){}
        }else {current.value=previous;message.value=result?.msg||"Unable to update profile picture"}
      }catch(e){current.value=previous;message.value="Unable to update profile picture"}
      finally{busy.value=false}
    };
    const upload=event=>{
      const file=event.target.files?.[0];
      if(!file)return;
      if(!/^image\/(png|jpeg|webp)$/.test(file.type)||file.size>1500000){
        message.value="Choose a PNG, JPG, or WebP image under 1.5 MB";
        return;
      }
      const reader=new FileReader();
      reader.onload=()=>savePhoto(reader.result).then(result=>{
        if(result&&result.code===0){
          current.value=reader.result;
          message.value="Profile picture updated";
          try{
            const info=JSON.parse(localStorage.getItem("userInfo")||"{}");
            info.userPhoto=reader.result;
            localStorage.setItem("userInfo",JSON.stringify(info));
            window.dispatchEvent(new CustomEvent("jalwa-avatar-updated",{detail:{userPhoto:reader.result}}));
          }catch(e){}
        }else message.value=result?.msg||"Unable to update profile picture";
      }).catch(()=>{message.value="Unable to update profile picture"});
      reader.readAsDataURL(file);
    };
    return()=>h("div",{class:"avatar-page",style:"min-height:100vh;background:#08052d;color:#fff;padding:24px 18px;font-family:Arial,sans-serif"},[
      h("div",{style:"display:flex;align-items:center;gap:12px;margin-bottom:24px"},[
        h("button",{onClick:close,style:"border:0;background:transparent;color:#fff;font-size:28px"},"‹"),
        h("h2",{style:"margin:0"},"Change avatar")
      ]),
      h("div",{style:"text-align:center;background:#071b55;border-radius:18px;padding:24px"},[
        h("img",{src:current.value,style:"width:116px;height:116px;border-radius:50%;object-fit:cover;border:4px solid #2ee6d1;background:#111"}),
        h("p",{style:"opacity:.8"},"Choose an avatar"),
        h("div",{class:"jalwa-avatar-grid",style:"display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin:18px 0"},avatarChoices.map((path,index)=>{
          const choice={id:String(index+1),path};
          return h("button",{
            key:choice.id,
            onClick:()=>choose(choice),
            disabled:busy.value,
            "aria-label":"Choose avatar "+choice.id,
            style:"position:relative;border:3px solid "+(current.value===choice.path?"#2ee6d1":"transparent")+";padding:2px;border-radius:50%;background:transparent;aspect-ratio:1;overflow:hidden;opacity:"+(busy.value?.65:1)
          },[h("img",{src:choice.path,style:"width:100%;height:100%;border-radius:50%;object-fit:cover;display:block"}),h("span",{style:"position:absolute;right:1px;bottom:1px;width:22px;height:22px;border-radius:50%;background:#21dcca;color:#06133e;font-size:16px;font-weight:900;line-height:22px;box-shadow:0 1px 4px #000;opacity:"+(current.value===choice.path?1:0)},"✓")]);
        })),
        h("label",{style:"display:inline-block;background:#21dcca;color:#06133e;padding:13px 22px;border-radius:24px;font-weight:700"},["Update picture",h("input",{type:"file",accept:"image/png,image/jpeg,image/webp",onChange:upload,style:"display:none"})]),
        busy.value?h("p",null,"Saving..."):null,
        message.value?h("p",{style:"color:#ffd166"},message.value):null
      ])
    ]);
  }
});
export default Avatar;
