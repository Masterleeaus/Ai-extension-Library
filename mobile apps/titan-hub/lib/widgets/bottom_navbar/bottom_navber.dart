import 'package:flutter/material.dart';
import 'package:flutter_svg/svg.dart';
import 'package:get/get.dart';
import 'package:qrpay/routes/routes.dart';
import 'package:qrpay/views/others/custom_image_widget.dart';

import '../../custom_assets/assets.gen.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';

// ignore_for_file: deprecated_member_use
buildBottomNavigationMenu(context, bottomNavBarController) {
  return BottomAppBar(
    elevation: 0,
    color: CustomColor.transparent,
    child: Container(
      height: Dimensions.heightSize * 5,
      decoration: BoxDecoration(
        color: CustomColor.primaryLightColor,
        borderRadius: BorderRadius.circular(Dimensions.radius * 3.22),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.max,
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: <Widget>[
          bottomItemWidget(Assets.icon.home, bottomNavBarController, 0),
          GestureDetector(
            onTap: () {
              Get.toNamed(Routes.qRCodeScreen);
            },
            child: CircleAvatar(
              backgroundColor: CustomColor.whiteColor.withOpacity(
                0.1,
              ),
              radius: 26,
              child: CustomImageWidget(
                path: Assets.icon.scan,
                width: Dimensions.heightSize * 2.2,
                height: Dimensions.heightSize * 2.2,
              ),
            ),
          ),
          bottomItemWidget(Assets.icon.kycUpdate, bottomNavBarController, 1),
        ],
      ),
    ),
  );
}

bottomItemWidget(var icon, bottomNavBarController, page) {
  return Expanded(
    child: GestureDetector(
      onTap: () {
        bottomNavBarController.selectedIndex.value = page;
      },
      child: SvgPicture.asset(
        icon,
        color: bottomNavBarController.selectedIndex.value == page
            ? CustomColor.whiteColor
            : CustomColor.whiteColor.withOpacity(0.4),
        height: Dimensions.iconSizeLarge,
      ),
    ),
  );
}
