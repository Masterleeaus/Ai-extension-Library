import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:qrpay/controller/categories/virtual_card/flutter_wave_virtual_card/virtual_card_controller.dart';

import '../../../../backend/model/common/common_success_model.dart';
import '../../../../backend/services/api_services.dart';
import '../../../../backend/utils/custom_snackbar.dart';
import '../../../../backend/utils/logger.dart';
import '../../../../routes/routes.dart';
import '../../../../utils/custom_color.dart';
import '../../../../utils/custom_style.dart';
import '../../../../utils/dimensions.dart';
import '../../../../widgets/text_labels/custom_title_heading_widget.dart';

final log = logger(AddFundController);

class AddFundController extends GetxController {
  final virtualCardController = Get.put(VirtualCardController());

  final amountTextController = TextEditingController();

  List<String> totalAmount = [];

  @override
  void dispose() {
    amountTextController.dispose();

    super.dispose();
  }

  @override
  void onInit() {
    amountTextController.text = '0.0';
    super.onInit();
  }

  goToAddMoneyPreviewScreen() {
    Get.toNamed(Routes.addFundPreviewScreen);
  }

  goToAddMoneyCongratulationScreen() {
    Get.toNamed(Routes.addFundPreviewScreen);
  }

  RxString selectItem = ''.obs;
  List<String> keyboardItemList = [
    '1',
    '2',
    '3',
    '4',
    '5',
    '6',
    '7',
    '8',
    '9',
    '.',
    '0',
    '<'
  ];
  List currencyList = ['USD', 'BDT'];
  List gatewayList = ['Paypal', 'Stripe'];

  inputItem(int index) {
    return InkWell(
      overlayColor: WidgetStateProperty.all(Colors.transparent),
      onLongPress: () {
        if (index == 11) {
          if (totalAmount.isNotEmpty) {
            totalAmount.clear();
            amountTextController.text = totalAmount.join('');
          } else {
            return;
          }
        }
      },
      onTap: () {
        if (index == 11) {
          if (totalAmount.isNotEmpty) {
            totalAmount.removeLast();
            amountTextController.text = totalAmount.join('');
          } else {
            return;
          }
        } else {
          if (totalAmount.contains('.') && index == 9) {
            return;
          } else {
            totalAmount.add(keyboardItemList[index]);
            amountTextController.text = totalAmount.join('');
            debugPrint(totalAmount.join(''));
          }
        }
        getFee(rate: virtualCardController.rate.value);
      },
      child: Center(
        child: CustomTitleHeadingWidget(
          text: keyboardItemList[index],
          style: Get.isDarkMode
              ? CustomStyle.lightHeading2TextStyle.copyWith(
                  fontSize: Dimensions.headingTextSize3 * 2,
                )
              : CustomStyle.darkHeading2TextStyle.copyWith(
                  color: CustomColor.primaryLightColor,
                  fontSize: Dimensions.headingTextSize3 * 2,
                ),
        ),
      ),
    );
  }

  // ---------------------------------------------------------------------------
  //                              Card Block Process
  // ---------------------------------------------------------------------------
  // -------------------------------Api Loading Indicator-----------------------
  //

  final _isLoading = false.obs;

  bool get isLoading => _isLoading.value;

  // -------------------------------Define API Model-----------------------------
  //
  late CommonSuccessModel _carAddFundModel;

  CommonSuccessModel get carAddFundModel => _carAddFundModel;

  // ------------------------------API Function---------------------------------
  //
  Future<CommonSuccessModel> carAddFundProcess(String cardId) async {
    _isLoading.value = true;
    Map<String, dynamic> inputBody = {
      'fund_amount': amountTextController.text,
      'card_id': cardId,
    };

    update();

    await ApiServices.carAddFundApi(body: inputBody).then((value) {
      _carAddFundModel = value!;

      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isLoading.value = false;
    update();
    return _carAddFundModel;
  }

  // -------------------------------Api Loading Indicator-----------------------

  late CommonSuccessModel _cardCreateData;

  CommonSuccessModel get cardCreateData => _cardCreateData;

  Future<CommonSuccessModel> cardCreateProcess() async {
    _isLoading.value = true;
    Map<String, dynamic> inputBody = {
      'card_amount': amountTextController.text,
    };

    update();

    await ApiServices.createCardApi(body: inputBody).then((value) {
      _cardCreateData = value!;

      CustomSnackBar.success(_cardCreateData.message.success.first);
      Get.offAllNamed(Routes.bottomNavBarScreen);

      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isLoading.value = false;
    update();
    return _cardCreateData;
  }

  RxDouble getFee({required double rate}) {
    double value =
        virtualCardController.dashboardController.fixedCharge.value * rate;
    value = value +
        (double.parse(amountTextController.text.isEmpty
                ? '0.0'
                : amountTextController.text) *
            (virtualCardController.dashboardController.percentCharge.value /
                100));

    if (amountTextController.text.isEmpty) {
      virtualCardController.totalFee.value = 0.0;
    } else {
      virtualCardController.totalFee.value = value;
    }

    debugPrint(virtualCardController.totalFee.value.toStringAsPrecision(2));
    return virtualCardController.totalFee;
  }
}
