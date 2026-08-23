import '../../../../utils/basic_screen_imports.dart';
import '../../../../utils/responsive_layout.dart';
import 'share_link_screen_mobile.dart';

class ShareLinkScreen extends StatelessWidget {
  const ShareLinkScreen({
    super.key,
    required this.title,
    required this.controller,
    required this.btnName,
    required this.onTap,
    required this.onButtonTap,
  });

  final String title, btnName;
  final VoidCallback onTap;
  final VoidCallback onButtonTap;
  final TextEditingController controller;

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
      mobileScaffold: ShareLinkScreenMobile(
        title: title,
        controller: controller,
        btnName: btnName,
        onTap: onTap,
        onButtonTap: onButtonTap,
      ),
    );
  }
}
