import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:qrpay/custom_assets/assets.gen.dart';

import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../views/others/custom_image_widget.dart';

class BackButtonWidget extends StatelessWidget {
  const BackButtonWidget({super.key, this.onTap});

  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    bool isTablet() {
      return MediaQuery.of(context).size.shortestSide >= 600;
    }

    return Padding(
      padding: EdgeInsets.only(
        left: isTablet()
            ? Dimensions.marginSizeHorizontal * 0.3
            : Dimensions.marginSizeHorizontal * 0.5,
        top: isTablet() ? 0 : Dimensions.marginSizeVertical * 0.5,
        bottom: isTablet() ? 0 : Dimensions.marginSizeVertical * 0.5,
      ),
      child: GestureDetector(
        onTap: onTap ??
            () {
              Get.back();
            },
        child: CircleAvatar(
          backgroundColor: CustomColor.primaryLightColor,
          child: CustomImageWidget(
            path: Assets.icon.backward,
            height: Dimensions.heightSize * 2,
            width: Dimensions.widthSize * 2.2,
          ),
        ),
      ),
    );
  }
}
