import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:qrpay/utils/custom_color.dart';
import 'package:qrpay/utils/dimensions.dart';
import 'package:qrpay/widgets/text_labels/title_heading4_widget.dart';

import 'back_button.dart';

class AppBarWidget extends StatelessWidget implements PreferredSizeWidget {
  final String text;
  final VoidCallback? onTapLeading;
  final VoidCallback? onTapAction;
  final bool homeButtonShow;
  final IconData? actionIcon;

  const AppBarWidget(
      {required this.text,
      this.onTapLeading,
      this.onTapAction,
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
            ? Dimensions.headingTextSize3
            : Dimensions.headingTextSize1,
        fontWeight: FontWeight.w500,
        color: Get.isDarkMode
            ? CustomColor.primaryDarkTextColor
            : CustomColor.primaryLightColor,
      ),
      elevation: 0,
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      actions: [
        Visibility(
          visible: homeButtonShow,
          child: IconButton(
              onPressed: onTapAction,
              icon: Icon(
                actionIcon ?? Icons.home,
                color: CustomColor.primaryLightColor,
              )),
        )
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
