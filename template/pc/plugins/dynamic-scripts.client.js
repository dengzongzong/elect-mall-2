import Setting from "@/setting";
export default async (context, inject) => {
  // 只在客户端执行
  if (process.client) {
    try {
      const response = await fetch(`${Setting.apiBaseURL}/custom_pc_js`);
      const content = await response.text();
      
      const isHTML = content.trim().startsWith('<script');
      let externalScripts = [];
      let inlineScripts = [];

      if (isHTML) {
        const parser = new DOMParser();
        const doc = parser.parseFromString(content, 'text/html');
        const scripts = doc.querySelectorAll('script');
        
        externalScripts = Array.from(scripts).filter(script => script.src);
        inlineScripts = Array.from(scripts).filter(script => !script.src);
      } else {
        inlineScripts = [{ textContent: content }];
      }

      // 加载外部脚本
      await Promise.all(externalScripts.map(script => {
        return new Promise((resolve, reject) => {
          const newScript = document.createElement('script');
          newScript.src = script.src;
          newScript.onload = resolve;
          newScript.onerror = reject;
          document.body.appendChild(newScript);
        });
      }));

      // 执行内联脚本
      inlineScripts.forEach(script => {
        const newScript = document.createElement('script');
        newScript.textContent = script.textContent;
        document.body.appendChild(newScript);
      });
    } catch (error) {
      console.error('Error loading dynamic scripts:', error);
    }
  }
}