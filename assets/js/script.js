/**
 * Artwork Auth - Main JS
 */

// Doküman hazır olduğunda çalışacak fonksiyonlar
document.addEventListener('DOMContentLoaded', function() {
    // AdminLTE menü açma-kapama
    var menuItems = document.querySelectorAll('.nav-sidebar .nav-item');
    
    menuItems.forEach(function(item) {
        // Doğrudan ilk seviye nav-link sınıfına sahip elemanı bul
        var link = null;
        for (var i = 0; i < item.children.length; i++) {
            if (item.children[i].classList.contains('nav-link')) {
                link = item.children[i];
                break;
            }
        }
        var submenu = item.querySelector('.nav-treeview');
        
        if (link && submenu) {
            // Tıklama olayı
            link.addEventListener('click', function(e) {
                e.preventDefault();
                item.classList.toggle('menu-open');
                
                if (item.classList.contains('menu-open')) {
                    submenu.style.display = 'block';
                } else {
                    submenu.style.display = 'none';
                }
            });
            
            // Başta aktif menüyü aç
            if (link.classList.contains('active') || item.querySelector('.nav-link.active')) {
                item.classList.add('menu-open');
                submenu.style.display = 'block';
            } else {
                submenu.style.display = 'none';
            }
        }
    });
    
    // DataTables (varsa) yapılandır
    if (typeof $.fn.DataTable !== 'undefined' && document.getElementById('dataTable')) {
        // Çakışmayı önlemek için, sadece tablo henüz DataTable olarak başlatılmamışsa başlat
        if (!$.fn.dataTable.isDataTable('#dataTable')) {
            $('#dataTable').DataTable({
                "responsive": true,
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.12.1/i18n/tr.json"
                }
            });
        }
    }
    
    // Sidebar toggle (mobil görünümde)
    const sidebarToggler = document.querySelector('[data-lte-toggle="sidebar"]');
    if (sidebarToggler) {
        sidebarToggler.addEventListener('click', function() {
            document.body.classList.toggle('sidebar-collapse');
        });
    }
    
    // Tooltips etkinleştir
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Otomatik kapanan alertler
    var alertList = document.querySelectorAll('.alert-dismissible');
    alertList.forEach(function (alert) {
        setTimeout(function() {
            var closeButton = alert.querySelector('.btn-close');
            if (closeButton) {
                closeButton.click();
            }
        }, 5000); // 5 saniye sonra kapat
    });
});

/**
 * AdminLTE TreeMenu
 * Açılır kapanır menü işlevselliğini sağlar
 */
function initTreeMenu() {
    var menuItems = document.querySelectorAll('[data-lte-toggle="treeview"]');
    
    menuItems.forEach(function(item) {
        // Ana menü öğelerini bul
        var menuParents = [];
        item.querySelectorAll('.nav-item').forEach(function(navItem) {
            // Her nav-item içindeki nav-link sınıfına sahip a etiketlerini bul
            for (var i = 0; i < navItem.children.length; i++) {
                if (navItem.children[i].classList.contains('nav-link') && navItem.children[i].tagName === 'A') {
                    menuParents.push(navItem.children[i]);
                }
            }
        });
        
        menuParents.forEach(function(menuParent) {
            if (menuParent.nextElementSibling && menuParent.nextElementSibling.classList.contains('nav-treeview')) {
                // Tıklama olayı ekle
                menuParent.addEventListener('click', function(e) {
                    e.preventDefault();
                    var parent = this.parentElement;
                    var treeviewMenu = this.nextElementSibling;
                    
                    // Açık veya kapalı durumunu değiştir
                    parent.classList.toggle('menu-open');
                    
                    // Menü görünürlüğünü güncelle
                    if (treeviewMenu.style.display === 'block') {
                        slideUp(treeviewMenu, 300);
                    } else {
                        slideDown(treeviewMenu, 300);
                    }
                });
                
                // Başlangıçta aktif olan menüyü aç
                if (menuParent.classList.contains('active') || isChildActive(menuParent.nextElementSibling)) {
                    menuParent.parentElement.classList.add('menu-open');
                    menuParent.nextElementSibling.style.display = 'block';
                } else {
                    menuParent.nextElementSibling.style.display = 'none';
                }
            }
        });
    });
}

/**
 * Alt menülerde aktif öğe var mı kontrol et
 */
function isChildActive(elem) {
    if (!elem) return false;
    
    var activeLinks = elem.querySelectorAll('.nav-link.active');
    return activeLinks.length > 0;
}

/**
 * Yukarı doğru kapat animasyonu
 */
function slideUp(element, duration) {
    element.style.height = element.offsetHeight + 'px';
    element.style.transitionProperty = 'height, margin, padding';
    element.style.transitionDuration = duration + 'ms';
    element.offsetHeight; // Reflow için
    element.style.overflow = 'hidden';
    element.style.height = 0;
    element.style.paddingTop = 0;
    element.style.paddingBottom = 0;
    element.style.marginTop = 0;
    element.style.marginBottom = 0;
    
    setTimeout(function() {
        element.style.display = 'none';
        element.style.removeProperty('height');
        element.style.removeProperty('padding-top');
        element.style.removeProperty('padding-bottom');
        element.style.removeProperty('margin-top');
        element.style.removeProperty('margin-bottom');
        element.style.removeProperty('overflow');
        element.style.removeProperty('transition-duration');
        element.style.removeProperty('transition-property');
    }, duration);
}

/**
 * Aşağı doğru aç animasyonu
 */
function slideDown(element, duration) {
    element.style.removeProperty('display');
    var display = window.getComputedStyle(element).display;
    if (display === 'none') display = 'block';
    element.style.display = display;
    
    var height = element.offsetHeight;
    element.style.overflow = 'hidden';
    element.style.height = 0;
    element.style.paddingTop = 0;
    element.style.paddingBottom = 0;
    element.style.marginTop = 0;
    element.style.marginBottom = 0;
    element.offsetHeight; // Reflow için
    
    element.style.transitionProperty = 'height, margin, padding';
    element.style.transitionDuration = duration + 'ms';
    element.style.height = height + 'px';
    element.style.removeProperty('padding-top');
    element.style.removeProperty('padding-bottom');
    element.style.removeProperty('margin-top');
    element.style.removeProperty('margin-bottom');
    
    setTimeout(function() {
        element.style.removeProperty('height');
        element.style.removeProperty('overflow');
        element.style.removeProperty('transition-duration');
        element.style.removeProperty('transition-property');
    }, duration);
}

/**
 * Silme işlemi için onay modalını göster
 * @param {string} url Silme URL'i
 * @param {string} title Modal başlığı
 * @param {string} message Onay mesajı
 */
function confirmDelete(url, title, message) {
    var modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    
    if (title) {
        document.getElementById('confirmModalLabel').textContent = title;
    }
    
    if (message) {
        document.getElementById('confirmModalBody').textContent = message;
    }
    
    document.getElementById('confirmDeleteBtn').href = url;
    modal.show();
}

/**
 * Doğrulama kodu kopyala
 * @param {string} code Kopyalanacak kod
 */
function copyVerificationCode(code) {
    navigator.clipboard.writeText(code).then(function() {
        alert('Doğrulama kodu kopyalandı: ' + code);
    }, function(err) {
        console.error('Kopyalama başarısız oldu: ', err);
    });
}

/**
 * Metni panoya kopyala
 * @param {string} elementId Kopyalanacak içeriğin bulunduğu elementin ID'si
 */
function copyToClipboard(elementId) {
    var copyText = document.getElementById(elementId);
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    document.execCommand("copy");
    
    // Kullanıcıya geri bildirim
    var toast = document.createElement('div');
    toast.classList.add('toast');
    toast.style.position = 'fixed';
    toast.style.top = '20px';
    toast.style.right = '20px';
    toast.style.background = '#28a745';
    toast.style.color = 'white';
    toast.style.padding = '10px 20px';
    toast.style.borderRadius = '4px';
    toast.style.zIndex = '9999';
    toast.innerHTML = '<i class="fas fa-check-circle"></i> Panoya kopyalandı!';
    document.body.appendChild(toast);
    
    setTimeout(function(){
        document.body.removeChild(toast);
    }, 3000);
}

// AdminLTE temel işlevleri 
$(document).ready(function() {
    // Treeview menü
    $('.nav-sidebar .nav-item:has(.nav-treeview)').each(function() {
        var parent = $(this);
        var treeview = parent.find('.nav-treeview').first();
        
        // Menu öğesine tıklama
        parent.find('> .nav-link').on('click', function(e) {
            e.preventDefault();
            
            parent.toggleClass('menu-open');
            if (parent.hasClass('menu-open')) {
                // Önce display:block yaparak görünür yap, sonra slide animasyonu uygula
                treeview.css('display', 'block').slideDown(300);
            } else {
                treeview.slideUp(300, function() {
                    // Slide bittikten sonra display:none yap
                    treeview.css('display', 'none');
                });
            }
        });
        
        // İlk açılışta, aktif menüleri aç
        if (parent.hasClass('menu-open') || parent.find('.nav-link.active').length || parent.find('.nav-treeview .nav-link.active').length) {
            parent.addClass('menu-open');
            treeview.css('display', 'block'); // Görünür yap
        } else {
            treeview.css('display', 'none'); // Gizle
        }
    });
}); 