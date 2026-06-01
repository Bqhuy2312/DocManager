import os
import glob

emojis = {
    '📄': '<i class="fas fa-file"></i>',
    '🏠': '<i class="fas fa-home"></i>',
    '📚': '<i class="fas fa-book"></i>',
    '⭐': '<i class="fas fa-star"></i>',
    '🏷️': '<i class="fas fa-tag"></i>',
    '📁': '<i class="fas fa-folder"></i>',
    '⬆️': '<i class="fas fa-arrow-up"></i>',
    '👥': '<i class="fas fa-users"></i>',
    '⚙️': '<i class="fas fa-cog"></i>',
    '🔍': '<i class="fas fa-search"></i>',
    '📋': '<i class="fas fa-list"></i>',
    '📊': '<i class="fas fa-chart-bar"></i>',
    '💬': '<i class="fas fa-comment"></i>',
    '🎓': '<i class="fas fa-graduation-cap"></i>',
    '📖': '<i class="fas fa-book"></i>',
    '📜': '<i class="fas fa-scroll"></i>',
    '🚪': '<i class="fas fa-door-open"></i>',
    '✏️': '<i class="fas fa-edit"></i>',
    '🗑️': '<i class="fas fa-trash"></i>',
    '⬇️': '<i class="fas fa-download"></i>',
    '✅': '<i class="fas fa-check"></i>',
    '📧': '<i class="fas fa-envelope"></i>',
    '📱': '<i class="fas fa-mobile"></i>',
    'ℹ️': '<i class="fas fa-info-circle"></i>',
    '🔐': '<i class="fas fa-lock"></i>',
    '🔑': '<i class="fas fa-key"></i>',
    '🌙': '<i class="fas fa-moon"></i>',
    '💾': '<i class="fas fa-save"></i>',
    '🕐': '<i class="fas fa-clock"></i>',
    '🌍': '<i class="fas fa-globe"></i>',
    '🔔': '<i class="fas fa-bell"></i>',
    '📅': '<i class="fas fa-calendar"></i>',
    '👤': '<i class="fas fa-user"></i>',
    '🏢': '<i class="fas fa-building"></i>',
    '🎫': '<i class="fas fa-ticket"></i>',
    '👁️': '<i class="fas fa-eye"></i>',
    '📝': '<i class="fas fa-pen"></i>',
    '📤': '<i class="fas fa-cloud-upload-alt"></i>',
    '🔖': '<i class="fas fa-bookmark"></i>',
    '➕': '<i class="fas fa-plus"></i>',
    '❌': '<i class="fas fa-times"></i>'
}

files = glob.glob('src/pages/*.vue') + glob.glob('src/components/*.vue')

for filepath in files:
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    for emoji, icon in emojis.items():
        content = content.replace(emoji, icon)
    
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
    
    print(f"Updated: {filepath}")

print("Done!")
