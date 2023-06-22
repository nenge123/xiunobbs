(function(){
    var T = this;I=T.I,F=T.F;
    Object.assign(T.action,{
        mask(){
            var mask = T.$ct('div','<label class="mask-label" style="height: 20px;"><b class="mask-title"></b>:<progress class="mask-progress" max="100" value="20"></progress></label>','mask-content');
            document.body.appendChild(mask);
            return [mask,T.$('.mask-progress',mask),T.$('.mask-title',mask)];

        }
    });

}).call(Nenge);