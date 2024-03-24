    !(async function (){
        var buffer = new Uint8Array(await (await fetch('/xiunobbs4/assets/js/webp_enc.wasm')).arrayBuffer());
        var ByteArr = ["\\n","\\f","\\r","\\t","\\a","\\v","\\b","\\a"];
        var NumArr = ByteArr.map(v=>(new Function('return  ("'+v+'").charCodeAt(0);'))());
        var to16 = num=>num.toString(16).padStart(2,0);
        var str = "";
        var str2 = "",str3=!1,len=0;
        for(var i=0;i<buffer.byteLength;i++){
            let byte = buffer[i],char;
            char = String.fromCharCode(byte);
            if(str3!==!1){
                if(str3==char){
                    len +=1;
                    continue;
                }else{
                    if(len>0){
                        str2+='['+str3+'~'+(len+1)+']';
                        len = 0;
                        str3 = !1;
                    }else{
                        str2+=str3;
                        str3 = !1;
                    }
                }
            }
            str3 = char;
        }
        //147961
        if(str3)str2+=str3;
        var list = str2.match(/(\[.{1}\~\d+?\])/g);
        var sb = str2;
        for(var k=1;k<list.length;k++){
            var mc = list[k].match(/\[.\~(\d+)\]/)[1];
            sb = sb.replace(list[k],"".padStart(parseInt(mc[2])+1 ,mc[1]));
        }
        var jj=[];
        for(var m=0;m<sb.length;m++){
            jj.push(sb.charCodeAt(m));
        }
        console.log(buffer);
        console.log(jj);
        var link =  document.createElement('a');
        link.download = 'asm.txt';
        link.href = URL.createObjectURL(new Blob([str2]));
        link.click();
        link.remove();
    })()