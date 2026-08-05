import 'package:flutter/material.dart';
import 'package:qrpay/utils/size.dart';
import 'package:qrpay/widgets/text_labels/title_heading1_widget.dart';

import '../../language/english.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';

class NoDataWidget extends StatelessWidget {
  const NoDataWidget({super.key, this.title});
  final String? title;

  @override
  Widget build(BuildContext context) {
    return Align(
      alignment: Alignment.center,
      child: Container(
        width: double.infinity,
        height: double.infinity,
        // height: Dimensions.buttonHeight * 5,
        padding: EdgeInsets.symmetric(vertical: Dimensions.paddingSize),
        margin: EdgeInsets.symmetric(horizontal: Dimensions.paddingSize),
        // decoration: BoxDecoration(
        //   color: CustomColor.primaryLightColor.withOpacity(0.29),
        //   borderRadius: BorderRadius.circular(Dimensions.radius),
        // ),
        child: Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(
                Icons.hourglass_empty,
                color: CustomColor.blackColor.withOpacity(0.4),
                size: Dimensions.iconSizeLarge * 1.5,
              ),
              verticalSpace(Dimensions.paddingSize * 0.3),
              TitleHeading1Widget(
                text: title ?? Strings.emptyStatus,
                color: CustomColor.blackColor.withOpacity(0.4),
                textAlign: TextAlign.center,
                fontSize: Dimensions.headingTextSize3,
              )
            ],
          ),
        ),
      ),
    );
  }
}
