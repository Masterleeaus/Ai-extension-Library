import '../../../routes/routes.dart';
import '../../../utils/basic_screen_imports.dart';
import '../../appbar/back_button.dart';

class StrowalletTransactionAppbar extends StatelessWidget
    implements PreferredSizeWidget {
  final String text;
  final VoidCallback? onTapLeading;

  final bool homeButtonShow;
  final IconData? actionIcon;

  const StrowalletTransactionAppbar(
      {required this.text,
      this.onTapLeading,
      this.homeButtonShow = false,
      this.actionIcon,
      super.key});

  @override
  Widget build(BuildContext context) {
    bool isTablet() {
      return MediaQuery.of(context).size.shortestSide >= 600;
    }

    return AppBar(
      title: TitleHeading4Widget(
        text: text,
        fontSize: isTablet()
            ? Dimensions.headingTextSize4
            : Dimensions.headingTextSize3,
        fontWeight: FontWeight.w500,
        color: Get.isDarkMode
            ? CustomColor.primaryDarkTextColor
            : CustomColor.primaryLightColor,
      ),
      elevation: 0,
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      actions: [
        InkWell(
          onTap: () {
            Get.toNamed(Routes.webhookLogsScreen);
          },
          child: TitleHeading3Widget(
            fontSize: Dimensions.headingTextSize4,
            padding: EdgeInsets.only(
              right: Dimensions.paddingHorizontalSize * 0.4,
            ),
            text: Strings.webhookLogs,
          ),
        ),
      ],
      leading: BackButtonWidget(
        onTap: onTapLeading ??
            () {
              Get.back();
            },
      ),
    );
  }

  @override
  Size get preferredSize => Size.fromHeight(Dimensions.appBarHeight * 0.7);
}
