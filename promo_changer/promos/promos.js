/*
    
    Created by Keith Marshall (mgraphic) - 9/24/2011
    keith@kmarshall.com
    
*/

jQuery(function(){
    
    var $ = jQuery;
    
    // Use defaults below
    $('.promo-changer').promos();
    
    
    // If you want a very slow transition fade, use the following example
    /*
    $('.promo-changer').promos({
        transitionTime:5000
    });
    */
    
    
    // If you want a slide fade, use the following example
    /*
    $('.promo-changer').promos({
        effect:'slide'
    });
    */
});


jQuery.fn.promos = function(config){
    
    var $ = jQuery;
    
    // Defualt Settings:
    var settings = {
        
        php : 'promos/',
        effect : 'fade',
        stillTime : 8000,
        transitionTime : 'slow',
        enableTabs : true,
        useTransitionOnTabClick : true,
        containerClassname : 'container',
        imgContainerClassname : 'img-container',
        tabsClassname : 'tabs',
        tabClassname : 'tab',
        tabActiveClassname : 'active'
    };
    
    if (typeof config == 'object') $.extend(settings, config);
    
    settings.data = [];
    
    $.ajax({
        
        url : settings.php,
        async : false,
        dataType : 'json',
        success : function(obj){
            
            if (typeof obj == 'object') for (i in obj) settings.data[i] = obj[i];
        }
    });
    
    $(this).each(function(idx){
        
        if (settings.data.length == 0) return;
        
        var self = this;
        
        self.images = [];
        
        self.timers = {hold : 0};
        
        self.current = 0;
        
        self.container = null;
        
        self.timers.display = function(){
            
            var count = self.images.length;
            
            $(self.images[0]).show();
            
            self.timers.hold = setInterval(function(){
                
                var previous = self.current++;
                
                if (self.current >= count) self.current = 0;
                
                self.change(previous, self.current);
                
            }, settings.stillTime);
        };
        
        self.change = function(from, to, isTab){
            
            if (self.images.length <= 1) return;
            
            var effect = (isTab && !settings.useTransitionOnTabClick) ? 'plain' : settings.effect;
            
            switch (effect) {
                
                case 'slide':
                    
                    var w = $(self.container).width();
                    
                    $(self.images[from]).animate({
                        
                        left : -w
                        
                    }, {
                        
                        duration : settings.transitionTime,
                        
                        complete : function(){
                            
                            $(this).hide().css('left', 0);
                        }
                    });
                    
                    $(self.images[to]).show().css('left', w).animate({
                        
                        left : 0
                        
                    }, settings.transitionTime);
                    
                break;
                case 'fade':
                    
                    $(self.images[from]).fadeOut(settings.transitionTime);
                    
                    $(self.images[to]).fadeIn(settings.transitionTime);
                    
                break;
                case 'plain':
                default:
                    
                    $(self.images[from]).hide();
                    
                    $(self.images[to]).show();
            }
            
            if (settings.enableTabs) {
                
                $(self).find('span[data-idx='+from+']').removeClass(settings.tabActiveClassname);
                
                $(self).find('span[data-idx='+to+']').addClass(settings.tabActiveClassname);
            }
        };
        
        self.make = function(){
            
            if (settings.enableTabs) {
                
                var tabs = $('<div/>').addClass(settings.tabsClassname);
                
                $(self).append(tabs);
            }
            
            self.container = $('<div/>').addClass(settings.containerClassname);
            
            for (i in settings.data) {
                
                var img = $('<img/>').attr('src', settings.data[i].img);
                
                var a = $('<a/>').attr('href', settings.data[i].url);
                
                var div = $('<div/>').addClass(settings.imgContainerClassname).hide();
                
                if (settings.data[i].url) {
                    
                    $(a).append(img);
                    
                    $(div).append(a);
                    
                    self.images[i] = div;
                    
                } else {
                    
                    $(div).append(img);
                    
                    self.images[i] = div;
                }
                
                $(self.container).append(self.images[i]);
                
                if (settings.enableTabs) {
                    
                    var tab = $('<span/>').attr('data-idx', i).addClass(settings.tabClassname).html(settings.data[i].title).click(function(){
                        
                        var idx = $(this).attr('data-idx');
                        
                        if (idx == self.current) return;
                        
                        clearInterval(self.timers.hold);
                        
                        self.change(self.current, idx, true);
                        
                        self.current = idx;
                        
                        self.timers.display();
                    });
                    
                    if (i == 0) $(tab).addClass(settings.tabActiveClassname);
                    
                    $(tabs).append(tab);
                }
            }
            
            $(self).append(self.container);
        };
        
        self.init = function(){
            
            self.make();
            
            self.timers.display();
        };
        
        self.init();
    });
    
    return $;
};

