from pathlib import Path
import json, sys
root=Path(__file__).resolve().parents[2]
checks=[]
def need(rel, needle=None):
    p=root/rel
    ok=p.is_file()
    if ok and needle is not None:
        ok=needle in p.read_text(errors='ignore')
    checks.append((rel if needle is None else f'{rel}::{needle}',ok))
need('System/GenerativeUI/BuilderRegistry.php')
need('System/GenerativeUI/GenerativeUiSpecValidator.php')
need('System/GenerativeUI/GenerativeUiSpecNormaliser.php')
need('System/GenerativeUI/GenerativeUiResponseComposer.php')
need('System/Http/Controllers/GenerativeUiController.php')
need('resources/assets/js/titan-generative-ui.js','TitanGenerativeUI')
need('resources/assets/css/titan-generative-ui.css','.tgui-root')
need('resources/views/frontend-ui/components/generative-ui-message.blade.php')
need('resources/views/home/generative-ui-lab.blade.php')
need('resources/builder/manifest.json','presentation-only')
need('System/Http/Resources/Api/ChatbotHistoryResource.php',"'ui_spec'")
need('System/Http/Controllers/Api/ChatbotApplicationController.php','GenerativeUiResponseComposer')
need('System/ChatbotServiceProvider.php','GenerativeUiController')
need('System/Http/Requests/ChatbotHistoryStoreRequest.php', "Rule::in(['user', 'assistant'])")
need('resources/titan-apps/TitanSuiteTemplates/TitanRegistry.php', "route('api.v2.titan.install'")
need('resources/views/frontend-ui/frontend-ui-scripts.blade.php','html: false')
failed=[name for name,ok in checks if not ok]
print(json.dumps({'checks':len(checks),'failed':failed},indent=2))
sys.exit(1 if failed else 0)
