import 'package:qrpay/backend/utils/custom_loading_api.dart';
import 'package:qrpay/utils/responsive_layout.dart';
import 'package:qrpay/widgets/appbar/appbar_widget.dart';

import '../../../../controller/categories/virtual_card/sudo_virtual_card/virtual_card_sudo_controller.dart';
import '../../../../utils/basic_screen_imports.dart';
import '../../../../widgets/others/preview/details_row_widget.dart';

class SudoCardDetailsScreen extends StatelessWidget {
  SudoCardDetailsScreen({super.key});

  final controller = Get.put(VirtualSudoCardController());

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
      mobileScaffold: Scaffold(
        appBar: const AppBarWidget(text: Strings.cardDetails),
        body: Obx(
          () => controller.isDetailsLoading
              ? const CustomLoadingAPI()
              : _bodyWidget(context),
        ),
      ),
    );
  }

  _bodyWidget(BuildContext context) {
    return Padding(
      padding: EdgeInsets.symmetric(
        horizontal: Dimensions.paddingSize * 0.9,
      ),
      child: Column(
        children: [
          _amountWidget(context),
          _cardDetailsWidget(context),
        ],
      ),
    );
  }

  _amountWidget(BuildContext context) {
    final data = controller.cardDetailsModel.data.cardDetails;
    final currency = controller.cardDetailsModel.data.baseCurr;
    return Container(
      alignment: Alignment.center,
      height: MediaQuery.of(context).size.height * 0.2,
      decoration: BoxDecoration(
        color: Get.isDarkMode
            ? CustomColor.primaryBGDarkColor
            : CustomColor.primaryBGLightColor,
        borderRadius: BorderRadius.circular(Dimensions.radius * 1.5),
      ),
      margin:
          EdgeInsets.symmetric(vertical: Dimensions.marginSizeVertical * 0.2),
      child: Column(
        mainAxisAlignment: mainCenter,
        children: [
          CustomTitleHeadingWidget(
            text: '${data.amount} $currency',
            textAlign: TextAlign.center,
            style: CustomStyle.darkHeading1TextStyle.copyWith(
              fontSize: Dimensions.headingTextSize4 * 2,
              fontWeight: FontWeight.w800,
              color: CustomColor.primaryLightColor,
            ),
          ),
          verticalSpace(Dimensions.heightSize * 0.5),
          CustomTitleHeadingWidget(
              text: Strings.currentBalance,
              textAlign: TextAlign.center,
              style: CustomStyle.darkHeading4TextStyle),
          verticalSpace(Dimensions.heightSize * 0.4),
          Container(
            alignment: Alignment.center,
            decoration: BoxDecoration(
                color: CustomColor.primaryLightColor,
                borderRadius: BorderRadius.circular(Dimensions.radius)),
            height: Dimensions.heightSize * 1.5,
            width: data.status == '1'
                ? Dimensions.widthSize * 5
                : Dimensions.widthSize * 7,
            child: CustomTitleHeadingWidget(
              text: data.status == '1' ? Strings.active : Strings.deActivated,
              textAlign: TextAlign.center,
              style: CustomStyle.darkHeading4TextStyle.copyWith(
                  color: CustomColor.whiteColor,
                  fontWeight: FontWeight.w600,
                  fontSize: Dimensions.headingTextSize2 * 0.5),
            ),
          )
        ],
      ),
    );
  }

  _cardDetailsWidget(BuildContext context) {
    final data = controller.cardDetailsModel.data.cardDetails;
    return Container(
      height: MediaQuery.of(context).size.height * 0.55,
      margin: EdgeInsets.only(top: Dimensions.heightSize * 0.4),
      decoration: BoxDecoration(
          color: Get.isDarkMode
              ? CustomColor.primaryBGDarkColor
              : CustomColor.primaryBGLightColor,
          borderRadius: BorderRadius.circular(Dimensions.radius * 1.5)),
      child: Column(crossAxisAlignment: crossStart, children: [
        Padding(
          padding: EdgeInsets.only(
            top: Dimensions.marginSizeVertical * 0.7,
            bottom: Dimensions.marginSizeVertical * 0.3,
            left: Dimensions.paddingSize * 0.7,
            right: Dimensions.paddingSize * 0.7,
          ),
          child: CustomTitleHeadingWidget(
            text: Strings.cardDetails,
            textAlign: TextAlign.left,
            style: Get.isDarkMode
                ? CustomStyle.f20w600pri.copyWith(color: CustomColor.whiteColor)
                : CustomStyle.f20w600pri,
          ),
        ),
        Divider(
          thickness: 1,
          color: CustomColor.primaryLightColor.withOpacity(0.2),
        ),
        Padding(
          padding: EdgeInsets.only(
            top: Dimensions.marginSizeVertical * 0.3,
            bottom: Dimensions.marginSizeVertical * 0.6,
            left: Dimensions.paddingSize * 0.7,
            right: Dimensions.paddingSize * 0.7,
          ),
          child: Column(children: [
            DetailsRowWidget(
              variable: Strings.cardType,
              value: data.type,
            ),
            DetailsRowWidget(
              variable: Strings.accountId,
              value: data.cardId,
              fontSizeValue: Dimensions.headingTextSize3 * 0.8,
            ),
            DetailsRowWidget(
              variable: Strings.cardPan,
              value: data.cardPan,
            ),
            DetailsRowWidget(
              variable: Strings.cvc,
              value: data.cvv,
            ),
            DetailsRowWidget(
              variable: Strings.validity,
              value: '${data.expiryYear} / ${data.expiryMonth}',
            ),
            Padding(
              padding: EdgeInsets.only(
                bottom: Dimensions.marginSizeVertical * 0.4,
              ),
              child: Row(
                mainAxisAlignment: mainSpaceBet,
                children: [
                  CustomTitleHeadingWidget(
                    text: Strings.status,
                    style: CustomStyle.darkHeading4TextStyle.copyWith(
                      color: CustomColor.primaryLightColor.withOpacity(0.4),
                    ),
                  ),
                  SizedBox(
                      width: MediaQuery.of(context).size.width * .35,
                      height: Dimensions.buttonHeight * 0.6,
                      child: Obx(
                        () => controller.isLoading
                            ? const CustomLoadingAPI()
                            : data.status == '1'
                                ? PrimaryButton(
                                    title: Strings.block,
                                    onPressed: () {
                                      controller.cardBlockProcess(data.cardId);
                                    },
                                  )
                                : PrimaryButton(
                                    title: Strings.unblock,
                                    onPressed: () {
                                      controller
                                          .cardUnBlockProcess(data.cardId);
                                    }),
                      )),
                ],
              ),
            )
          ]),
        ),
      ]),
    );
  }
}
